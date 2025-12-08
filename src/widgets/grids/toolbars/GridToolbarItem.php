<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagVisibilityTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class GridToolbarItem extends Widget
{
    use ContainerConfigurationTrait;
    use GridTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use TagVisibilityTrait;

    protected function renderContent(): string|Stringable
    {
        return $this->content
            ? Div::make()
                ->attributes($this->attributes)
                ->content(...$this->content)
            : '';
    }
}
