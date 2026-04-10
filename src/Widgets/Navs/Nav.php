<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Navs\Traits\NavItemTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Nav extends Widget
{
    use NavItemTrait;
    use TagAttributesTrait;
    use VisibilityTrait;

    protected bool $showSingleItem = false;

    public function showSingleItem(bool $show = true): static
    {
        $this->showSingleItem = $show;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        if (!$this->isVisible() || !$this->items || (count($this->items) === 1 && !$this->showSingleItem)) {
            return '';
        }

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('nav')
            ->content(...$this->items);
    }
}
