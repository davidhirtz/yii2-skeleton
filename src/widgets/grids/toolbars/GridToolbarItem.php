<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\toolbars;

use Hirtz\Skeleton\base\traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagVisibilityTrait;
use Hirtz\Skeleton\widgets\grids\traits\GridTrait;
use Hirtz\Skeleton\widgets\Widget;
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
