<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\FilterDropdown;
use Yii;
use yii\db\ActiveRecordInterface;

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
            ->href($this->getRoute(...))
            ->nowrap();
    }

    protected function getTypeIconColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('type')
            ->header(false)
            ->href($this->getRoute(...))
            ->content($this->getTypeColumnContent(...))
            ->visible($this->hasVisibleTypes())
            ->centered();
    }

    protected function getTypeColumnContent(ActiveRecordInterface&TypeAttributeInterface $model): string
    {
        return $this->getTypeIcon($model);
    }

    protected function getTypeDropdown(): ?FilterDropdown
    {
        return $this->hasVisibleTypes()
            ? new FilterDropdown(
                $this->getTypeDropdownItems(),
                Yii::t('skeleton', 'Type'),
                $this->typeParamName,
                $this->typeDefaultItem,
            )
            : null;
    }

    protected function getTypeDropdownItems(): array
    {
        $model = $this->getModel();

        return $model instanceof TypeAttributeInterface
            ? array_map(fn ($model) => $model->getTypePlural(), $model::getTypeInstances())
            : [];
    }

    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        return Html::icon($model->getTypeIcon())
            ->tooltip($model->getTypeName())
            ->render();
    }

    protected function hasVisibleTypes(): bool
    {
        $model = $this->getModel();
        return $model instanceof TypeAttributeInterface && !$this->type && count($model::getTypes()) > 1;
    }
}
