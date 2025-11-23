<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\FilterDropdown;
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
            ->url(fn ($model) => $this->getRoute($model))
            ->nowrap();
    }

    protected function getTypeIconColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('type')
            ->header(false)
            ->url(fn ($model) => $this->getRoute($model))
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
                ->param($this->typeParamName)
                ->default($this->typeDefaultItem)
            : null;
    }

    protected function getTypeDropdownItems(): array
    {
        $model = $this->getModel();

        return $model instanceof TypeAttributeInterface
            ? array_map(fn ($model) => $model->getTypePlural(), $model::getTypeInstances())
            : [];
    }

    protected function hasVisibleTypes(): bool
    {
        $model = $this->getModel();
        return $model instanceof TypeAttributeInterface && !$this->type && count($model::getTypes()) > 1;
    }
}
