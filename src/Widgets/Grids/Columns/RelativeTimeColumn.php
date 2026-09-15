<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Stringable;
use yii\base\Model;

/**
 * @template TModel of array|Model = Model
 * @extends LinkColumn<TModel>
 */
class RelativeTimeColumn extends LinkColumn
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-nowrap'];
        $this->content ??= $this->getRelativeTime(...);

        parent::__construct($config);
    }

    /**
     * @param TModel $model
     */
    protected function getRelativeTime(array|Model $model): string|Stringable
    {
        $timestamp = $this->getPropertyValue($model);
        return $timestamp ? RelativeTime::make()->value($timestamp) : '';
    }
}
