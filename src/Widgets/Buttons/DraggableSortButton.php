<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;

class DraggableSortButton extends Widget
{
    use IconTrait;
    use TagAttributesTrait;

    protected function renderContent(): string
    {
        $this->attributes['class'] ??= 'btn btn-secondary';

        return Button::make()
            ->attributes($this->attributes)
            ->addClass('sortable-handle')
            ->icon($this->icon ?? 'arrows-alt')
            ->render();
    }
}
