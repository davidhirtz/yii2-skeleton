<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;
use Yii;

trait TypeGridViewTrait
{
    protected ?int $type = null;
    protected string|false|null $typeDefaultItem = null;
    protected string $typeParamName = 'type';

    protected function getTypeColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('typeName')
            ->visible($this->hasVisibleTypes())
            // todo
//            ->url(fn ($model) => $model)
            ->nowrap();
    }

    protected function getTypeIconColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('type')
            ->title(false)
        // todo
//            ->url(fn ($model) => $this->getRoute($model))
            ->content($this->getTypeIconColumnContent(...))
            ->visible($this->hasVisibleTypes())
            ->centered();
    }

    protected function getTypeIconColumnContent(TypeAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($this->getTypeIcon($model))
            ->tooltip($model->getTypeName());
    }

    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        return $model->getTypeIcon();
    }

    protected function getTypeDropdown(): ?FilterDropdown
    {
        return $this->hasVisibleTypes()
            ? FilterDropdown::make()
                ->label(Yii::t('skeleton', 'Type'))
                ->items($this->getTypeDropdownItems())
                ->paramName($this->typeParamName)
                ->default($this->typeDefaultItem)
            : null;
    }

    protected function getTypeDropdownItems(): array
    {
        // todo
        return [];
//        return $this->model instanceof TypeAttributeInterface
//            ? array_map(fn ($model) => $model->getTypePlural(), $this->model::getTypeInstances())
//            : [];
    }

    protected function hasVisibleTypes(): bool
    {
        // todo
        return false;
//        return $this->model instanceof TypeAttributeInterface && !$this->type && count($this->model::getTypes()) > 1;
    }
}
