<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;

class TypeIconColumn extends LinkColumn
{
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->content ??= $this->getTypeIcon(...);
        $this->property ??= 'type';
        $this->title ??= false;

        parent::__construct($config);
    }

    protected function getTypeIcon(TypeAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($model->getTypeIcon())
            ->tooltip($model->getTypeName());
    }
}
