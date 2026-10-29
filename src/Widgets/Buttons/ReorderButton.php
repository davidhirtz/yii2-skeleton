<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Yii;

class ReorderButton extends Widget
{
    use IconTrait;
    use TagAttributesTrait;

    protected function renderContent(): string
    {
        $this->attributes['class'] ??= 'btn btn-secondary';
        $this->attributes['aria-label'] ??= Yii::t('skeleton', 'GRID_REORDER_LABEL');

        return Button::make()
            ->attributes($this->attributes)
            ->addClass('reorder-handle')
            ->icon($this->icon ?? 'arrows-alt')
            ->render();
    }
}
