<?php

declare(strict_types=1);

namespace Dpi\LayoutBuilderBundle\Component;

use Drupal\block_content\BlockContentInterface;

final class BlockContent implements ComponentInterface
{
    public string $blockPluginId;

    /**
     * @var array{
     *   view_mode?: string,
     *   block_id?: int,
     *   block_revision_id?: int,
     *   block_serialized?: string,
     * } $configuration
     *
     * @see \Drupal\layout_builder\Plugin\Block\InlineBlock::defaultConfiguration
     */
    public array $configuration = [];

    private function __construct(
        public BlockContentInterface $blockContent,
        public ?string $viewMode = null,
    ) {
        $this->blockPluginId = \sprintf('inline_block:%s', $this->blockContent->bundle());
        $this->configuration += ['block_revision_id' => (int) ($this->blockContent->getRevisionId() ?? throw new \Exception('Save the block content!'))];
        if (null !== $this->viewMode) {
            $this->configuration['view_mode'] = $this->viewMode;
        }
    }

    public static function create(
        BlockContentInterface $blockContent,
        ?string $viewMode = null,
    ): static {
        return new static($blockContent, $viewMode);
    }
}
