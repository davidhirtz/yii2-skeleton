<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\ButtonToolbar;
use Override;

class ButtonColumn extends Column
{
    protected array|null|Closure $contentAttributes = [
        'class' => 'text-end text-nowrap',
    ];

    #[Override]
    protected function getBodyContent($model, $key, $index): string
    {
        if ($this->content instanceof Closure) {
            $html = call_user_func($this->content, $model, $key, $index, $this);

            return ButtonToolbar::make()
                ->addHtml(...(array)$html)
                ->render();
        }

        return parent::getBodyContent($model, $key, $index);
    }
}
