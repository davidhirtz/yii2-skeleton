<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use yii\base\Model;

/**
 * @template TModel of array|Model = Model
 * @extends LinkColumn<TModel>
 */
class TypeColumn extends LinkColumn
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->property ??= 'type';
        $this->value ??= fn (TypeAttributeInterface $model): string => $model->getTypeName();

        $this->nowrap();

        parent::__construct($config);
    }
}
