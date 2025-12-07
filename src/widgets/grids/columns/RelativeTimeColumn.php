<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\html\custom\RelativeTime;
use Override;
use Stringable;
use yii\base\Model;

class RelativeTimeColumn extends LinkColumn
{
    public array|null|Closure $contentAttributes = ['class' => 'text-nowrap'];
    protected string $format = 'raw';

    #[Override]
    protected function getValue(array|Model $model, string|int $key, int $index): Stringable
    {
        $timestamp = ArrayHelper::getValue($model, $this->property);
        return RelativeTime::make()->value($timestamp);
    }
}
