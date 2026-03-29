<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Widget;

class DraggableSortGridButton extends Widget
{
    use IconTextTrait;

    public function renderContent(): string
    {
        return Button::make()
            ->secondary()
            ->icon($this->icon ?? 'arrows-alt')
            ->addClass('sortable-handle')
            ->render();
    }
}
