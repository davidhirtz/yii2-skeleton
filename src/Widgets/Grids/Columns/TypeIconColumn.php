<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;
use yii\base\Model;

/**
 * @template TModel of TypeAttributeInterface&Model = TypeAttributeInterface&Model
 * @extends LinkColumn<TModel>
 */
class TypeIconColumn extends LinkColumn
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->format ??= 'raw';
        $this->property ??= 'type';
        $this->title ??= false;
        $this->value ??= $this->getTypeIcon(...);

        parent::__construct($config);
    }

    protected function getTypeIcon(TypeAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($model->getTypeIcon())
            ->tooltip($model->getTypeName());
    }
}
