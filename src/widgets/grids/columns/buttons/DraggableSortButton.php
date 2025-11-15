<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns\buttons;

use davidhirtz\yii2\skeleton\html\Button;

class DraggableSortButton extends GridButton
{
    public function renderContent(): string
    {
        return Button::make()
            ->secondary()
            ->icon($this->icon ?? 'arrows-alt')
            ->addClass('sortable-handle')
            ->render();
    }
}
