<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Nav extends Widget
{
    /** @use ItemTrait<NavItem> */
    use ItemTrait;

    use TagAttributesTrait;
    use VisibilityTrait;

    protected function renderContent(): string|Stringable
    {
        if (!$this->isVisible()) {
            return '';
        }

        $content = implode('', array_map(strval(...), $this->items));

        return $content
            ? Ul::make()
                ->attributes($this->attributes)
                ->addClass('nav')
                ->content($content)
            : '';
    }
}
