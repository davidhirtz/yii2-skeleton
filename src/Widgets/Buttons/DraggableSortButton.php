<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;

class DraggableSortButton extends Widget
{
    use IconTrait;

    protected function renderContent(): string
    {
        return Button::make()
            ->secondary()
            ->icon($this->icon ?? 'arrows-alt')
            ->addClass('sortable-handle')
            ->render();
    }
}
