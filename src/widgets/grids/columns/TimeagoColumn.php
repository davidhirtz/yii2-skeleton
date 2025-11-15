<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\timeago\Timeago;
use yii\base\Model;

class TimeagoColumn extends LinkColumn
{
    public array|null|Closure $contentAttributes = [
        'class' => 'text-nowrap',
    ];

    protected string $format = 'raw';

    protected function getValue(array|Model $model, string|int $key, int $index): ?string
    {
        $timestamp = ArrayHelper::getValue($model, $this->property);
        return $timestamp ? Timeago::tag($timestamp) : null;
    }
}
