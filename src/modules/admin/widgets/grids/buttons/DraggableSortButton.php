<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\buttons;

use davidhirtz\yii2\skeleton\html\Button;
use Stringable;

readonly class DraggableSortButton implements Stringable
{
    public function __construct(
        private ?string $icon = 'arrows-alt',
    ) {
    }

    public function __toString(): string
    {
        return Button::secondary()
            ->icon($this->icon)
            ->addClass('sortable-handle')
            ->render();
    }
}
