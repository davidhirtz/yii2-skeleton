<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Override;
use Stringable;
use yii\base\Model;

class RelativeTimeColumn extends LinkColumn
{
    public array|null|Closure $contentAttributes = ['class' => 'text-nowrap'];
    protected string $format = 'raw';

    #[Override]
    protected function getValue(array|Model $model, string|int $key, int $index): string
    {
        $timestamp = ArrayHelper::getValue($model, $this->property);
        return RelativeTime::make()->value($timestamp)->render();
    }
}
