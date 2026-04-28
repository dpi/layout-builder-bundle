<?php

declare(strict_types=1);

namespace Dpi\LayoutBuilderBundle\Entity;

/**
 * @phpstan-property \Drupal\layout_builder\Field\LayoutSectionItemList<\Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem> $layout_builder__layout
 * @phpstan-property \Dpi\LayoutBuilderBundle\LayoutBuilder $layoutBuilder
 */
interface LayoutBuilderEntityInterface extends \Drupal\Core\Entity\ContentEntityInterface
{
    //  public \Dpi\LayoutBuilderBundle\LayoutBuilder $layoutBuilder { get; }
}
