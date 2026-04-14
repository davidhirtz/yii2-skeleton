<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\StickyTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class GridToolbar extends Widget
{
    use ContainerConfigurationTrait;
    use StickyTrait;
    use TagAttributesTrait;
    use TagContentTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->content
            ? Div::make()
                ->attributes($this->attributes)
                ->content(...$this->content)
            : '';
    }
}
