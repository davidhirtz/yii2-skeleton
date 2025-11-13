<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use Override;
use yii\grid\Column;

class ButtonsColumn extends Column
{
    public $contentOptions = [
        'class' => 'text-end text-nowrap',
    ];

    #[Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($this->content !== null) {
            $html = call_user_func($this->content, $model, $key, $index, $this);

            return ButtonToolbar::make()
                ->addHtml(...(array)$html)
                ->render();
        }

        return $this->grid->emptyCell;
    }
}
