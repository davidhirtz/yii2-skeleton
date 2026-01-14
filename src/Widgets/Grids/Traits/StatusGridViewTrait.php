<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Stringable;
use Yii;

trait StatusGridViewTrait
{
    protected string|false|null $statusDefaultItem = null;
    protected string $statusParamName = 'status';

    protected function getStatusColumn(): Column
    {
        return LinkColumn::make()
            ->property('status')
            ->header(false)
            ->content($this->getStatusIcon(...))
            ->url(fn ($model) => $this->getRoute($model))
            ->centered();
    }

    protected function getStatusIcon(StatusAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($model->getStatusIcon())
            ->tooltip($model->getStatusName());
    }

    public function getStatusDropdown(): FilterDropdown
    {
        return FilterDropdown::make()
            ->label(Yii::t('skeleton', 'Status'))
            ->items($this->getStatusDropdownItems())
            ->param($this->statusParamName)
            ->default($this->statusDefaultItem);
    }

    protected function getStatusDropdownItems(): array
    {
        return array_map(fn ($options) => $options['plural'] ?? $options['name'], $this->model::getStatuses());
    }
}
