<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Iterator;
use Override;
use Stringable;
use yii\base\Model;

class ButtonColumn extends Column
{
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-end'];
        parent::__construct($config);
    }

    #[Override]
    protected function getBody(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $buttons = ($this->content)($model, $key, $index, $this);

        if (is_string($buttons)) {
            $buttons = [$buttons];
        }

        if ($buttons instanceof Iterator) {
            $buttons = iterator_to_array($buttons);
        }

        if (is_array($buttons)) {
            return Div::make()
                ->class('btn-group')
                ->content(...$buttons);
        }

        return parent::getBody($model, $key, $index);
    }
}
