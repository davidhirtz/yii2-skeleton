<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\Div;
use Override;
use Stringable;
use yii\base\Model;

class ButtonColumn extends Column
{
    public array|null|Closure $contentAttributes = [
        'class' => 'text-end text-nowrap',
    ];

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content instanceof Closure) {
            $buttons = call_user_func($this->content, $model, $key, $index, $this);

            if (is_string($buttons)) {
                $buttons = [$buttons];
            }

            if (is_array($buttons)) {
                return Div::make()
                    ->class('btn-toolbar')
                    ->addContent(...$buttons);
            }
        }

        return parent::getBodyContent($model, $key, $index);
    }
}
