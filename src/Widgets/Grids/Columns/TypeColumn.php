<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

class TypeColumn extends LinkColumn
{
    public function __construct(array $config = [])
    {
        $this->property ??= 'type';
        $this->value ??= fn (TypeAttributeInterface $model): string => $model->getTypeName();

        $this->nowrap();

        parent::__construct($config);
    }
}
