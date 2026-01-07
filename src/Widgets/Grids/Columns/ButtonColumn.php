<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Override;
use Stringable;
use yii\base\Model;

class ButtonColumn extends Column
{
    public array|null|Closure $contentAttributes = [
        'class' => 'text-end',
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
                    ->class('btn-group')
                    ->content(...$buttons);
            }
        }

        return parent::getBodyContent($model, $key, $index);
    }
}
