<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\StickyTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class GridFooter extends Widget
{
    use ContainerConfigurationTrait;
    use ItemTrait;
    use StickyTrait;
    use TagAttributesTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->items
            ? Div::make()
                ->attributes($this->attributes)
                ->addClass('grid-footer')
                ->content(...$this->items)
            : '';
    }
}
