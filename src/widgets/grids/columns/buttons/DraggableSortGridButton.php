<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\columns\buttons;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\traits\TagIconTextTrait;
use Hirtz\Skeleton\widgets\Widget;

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
