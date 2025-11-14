<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use Override;

class ButtonsColumn extends Column
{
    protected function init(): void
    {
        $this->contentAttributes ??= [
            'class' => 'text-end text-nowrap',
        ];

        parent::init();
    }

    #[Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($this->content instanceof Closure) {
            $html = call_user_func($this->content, $model, $key, $index, $this);

            return ButtonToolbar::make()
                ->addHtml(...(array)$html)
                ->render();
        }

        return parent::renderDataCellContent($model, $key, $index);
    }
}
