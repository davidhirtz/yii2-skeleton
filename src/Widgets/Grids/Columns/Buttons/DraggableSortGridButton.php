<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Traits\TagIconTextTrait;
use Hirtz\Skeleton\Widgets\Widget;

class DraggableSortGridButton extends Widget
{
    use TagIconTextTrait;

    public function renderContent(): string
    {
        return Button::make()
            ->secondary()
            ->icon($this->icon ?? 'arrows-alt')
            ->addClass('sortable-handle')
            ->render();
    }
}
