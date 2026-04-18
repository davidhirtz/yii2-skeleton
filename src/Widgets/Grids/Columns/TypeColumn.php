<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;

class TypeColumn extends LinkColumn
{
    public function __construct(array $config = [])
    {
        $this->content ??= fn (TypeAttributeInterface $model): string => $model->getTypeName();
        $this->property ??= 'type';

        $this->nowrap();

        parent::__construct($config);
    }
}
