<?php

declare(strict_types=1);

namespace Dpi\LayoutBuilderBundle;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Field\LayoutSectionItemList;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * @todo a blockContentSectionComponentTemplate(string $className, callable $c).
 *
 * @implements \ArrayAccess<int, \Drupal\layout_builder\Section>
 */
final class LayoutBuilder implements \ArrayAccess, \Countable
{
    /** @var \SplObjectStorage<Entity\LayoutBuilderEntityInterface, self> */
    private static \SplObjectStorage $tracker;

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
     * Reconnect LayoutBuilder to the cloned entity instance.
     *
     * Handle the special case that causes the clone in entity forms to disconnect references:
     */
    public static function cloneFromNewEntity(Entity\LayoutBuilderEntityInterface $entity): void
    {
        $entity->layoutBuilder = clone $entity->layoutBuilder;
        $entity->layoutBuilder->entity = $entity;
    }

    public function addToSection(
        Section $section,
        BlockContentInterface|Component\BlockPlugin $item,
        ?string $viewMode = null,
        string $region = 'content',
    ): static {
        $id = $item instanceof BlockContentInterface
          ? \sprintf('inline_block:%s', $item->bundle())
          : $item->blockPluginId;

        $configuration = $item instanceof BlockContentInterface
          ? ['block_revision_id' => $item->getRevisionId() ?? throw new \Exception('Save it!')]
          : $item->configuration;

        $section->appendComponent(new SectionComponent(
            uuid: static::uuid()->generate(),
            region: $region,
            configuration: [
                'id' => $id,
                'label_display' => false,
            ] + [
                ...(null !== $viewMode ? ['view_mode' => $viewMode] : []),
            ] + $configuration,
        ));

        return $this;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Section) {
            $this->list()->appendSection($value);
        } else {
            throw new \LogicException('Not implemented');
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
