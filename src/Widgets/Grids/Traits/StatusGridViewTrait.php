<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\StatusIconColumn;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Yii;

trait StatusGridViewTrait
{
    protected string|false|null $statusDefaultItem = null;
    protected string $statusParamName = 'status';

    protected function getStatusColumn(): Column
    {
        return StatusIconColumn::make();
    }

    protected function getStatusDropdown(): FilterDropdown
    {
        return FilterDropdown::make()
            ->label(Yii::t('skeleton', 'Status'))
            ->items($this->getStatusDropdownItems())
            ->paramName($this->statusParamName)
            ->default($this->statusDefaultItem);
    }

    abstract protected function getStatusDropdownItems(): array;
}
