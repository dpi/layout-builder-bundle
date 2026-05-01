<?php

declare(strict_types=1);

namespace Dpi\LayoutBuilderBundle;

use Dpi\LayoutBuilderBundle\Component\ComponentInterface;
use Drupal\block_content as BlockContent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Field\LayoutSectionItemList;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * @implements \ArrayAccess<int, \Drupal\layout_builder\Section>
 */
final class LayoutBuilder implements \ArrayAccess, \Countable
{
    /** @var \SplObjectStorage<Entity\LayoutBuilderEntityInterface, self> */
    private static \SplObjectStorage $tracker;

    /** @var (\Closure(LayoutBuilder $this, ComponentInterface): Section)|null */
    public ?\Closure $sectionTemplate = null;

    private function __construct(
        private Entity\LayoutBuilderEntityInterface $entity,
    ) {
    }

    public static function from(Entity\LayoutBuilderEntityInterface $entity): static
    {
        static::$tracker ??= new \SplObjectStorage();
        /** @var static $lb */
        $lb = static::$tracker[$entity] ??= new static($entity);

        return $lb;
    }

    /**
     * @param-closure-this \Dpi\LayoutBuilderBundle\LayoutBuilder $template
     *
     * @param \Closure(ComponentInterface): Section $template
     */
    public function sectionTemplate(\Closure $template): void
    {
        $this->sectionTemplate = $template;
        $this->sectionTemplate = $this->sectionTemplate->bindTo($this);
    }

    /**
     * Reconnect LayoutBuilder to the cloned entity instance.
     *
     * Handle the special case that causes the clone in entity forms to disconnect references:
     */
    public static function cloneFromNewEntity(Entity\LayoutBuilderEntityInterface $entity): void
    {
        // @todo switch to tracker as we cannot guarantee $layoutBuilder, and remove the trait and interface^
        $entity->layoutBuilder = clone $entity->layoutBuilder;
        $entity->layoutBuilder->entity = $entity;
    }

    public function addToSection(
        Section $section,
        BlockContent\BlockContentInterface|ComponentInterface $item,
        string $region = 'content',
    ): static {
        $item = $item instanceof BlockContent\BlockContentInterface ? Component\BlockContent::create(blockContent: $item, configuration: []) : $item;

        $section->appendComponent(new SectionComponent(
            uuid: static::uuid()->generate(),
            region: $region,
            configuration: [
                'id' => $item->blockPluginId,
                'label_display' => false,
            ] + $item->configuration
        ));

        return $this;
    }

    /**
     * @phpstan-param Section|ComponentInterface|BlockContent\BlockContentInterface $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Section) {
            $this->list()->appendSection($value);
        } elseif ($value instanceof ComponentInterface || $value instanceof BlockContent\BlockContentInterface) {
            $value = $value instanceof BlockContent\BlockContentInterface ? Component\BlockContent::create(blockContent: $value, configuration: []) : $value;

            if (null !== $this->sectionTemplate) {
                $this->list()->appendSection(($this->sectionTemplate)($value));
            } else {
                throw new \LogicException('Section template is not set. Call sectionTemplate beforehand with a closure.');
            }
        } else {
            throw new \LogicException('Not supported');
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        throw new \LogicException('Not implemented');
    }

    public function offsetGet(mixed $offset): mixed
    {
        throw new \LogicException('Not implemented');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Not implemented');
    }

    public function count(): int
    {
        return \count($this->list()->getSections());
    }

    public function reset(): static
    {
        // @phpstan-ignore-next-line
        $this->entity->layout_builder__layout = [];

        return $this;
    }

    public function getLayoutBuilderOverrideUrl(): Url
    {
        if ($this->entity->isNew()) {
            throw new \LogicException('Entity must be saved.');
        }

        return Url::fromRoute(\sprintf('layout_builder.overrides.%s.view', $this->entity->getEntityTypeId()), [
            $this->entity->getEntityTypeId() => $this->entity->id(),
        ]);
    }

    /**
     * @todo convert to 8.4 asym vis.
     *
     * @phpstan-return LayoutSectionItemList<\Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem>
     */
    private function list(): LayoutSectionItemList
    {
        return $this->entity->layout_builder__layout;
    }

    private static function uuid(): UuidInterface
    {
        return \Drupal::service(UuidInterface::class);
    }
}
