<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;
use yii\base\Model;

/**
 * @template TModel of array|Model = Model
 * @extends LinkColumn<TModel>
 */
class StatusIconColumn extends LinkColumn
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->format ??= 'raw';
        $this->property ??= 'status';
        $this->title ??= false;
        $this->value ??= $this->getStatusIcon(...);

        parent::__construct($config);
    }

    protected function getStatusIcon(StatusAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($model->getStatusIcon())
            ->tooltip($model->getStatusName());
    }
}
