<?php

declare(strict_types=1);

namespace Dpi\LayoutBuilderBundle\Component;

use Drupal\Core\Block\Attribute\Block;

final class BlockPlugin
{
    /**
     * @phpstan-param array<string, mixed> $configuration
     */
    private function __construct(
        public string $blockPluginId,
        public array $configuration,
    ) {
    }

    /**
     * @phpstan-param string|class-string<\Drupal\Core\Block\BlockPluginInterface> $blockPlugin
     * @phpstan-param array<string, mixed> $configuration
     */
    public static function create(string $blockPlugin, array $configuration): static
    {
        if (\class_exists($blockPlugin)) {
            $r = ((new \ReflectionClass($blockPlugin))->getAttributes(Block::class)[0] ?? null)?->newInstance();
            if (null !== $r) {
                $blockPlugin = $r->id;
            }
        }

        return new static($blockPlugin, $configuration);
    }
}
