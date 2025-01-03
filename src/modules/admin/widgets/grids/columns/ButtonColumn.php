<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\grid\Column;

class ButtonColumn extends Column
{
    public $contentOptions = [
        'class' => 'text-end text-nowrap',
    ];

    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($this->content !== null) {
            $buttons = call_user_func($this->content, $model, $key, $index, $this);
            return Html::buttons($buttons);
        }

        return $this->grid->emptyCell;
    }
}
