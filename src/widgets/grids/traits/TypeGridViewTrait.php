<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\grids\FilterDropdown;
use Yii;

trait TypeGridViewTrait
{
    protected ?int $type = null;
    protected string|false|null $typeDefaultItem = null;
    protected string $typeParamName = 'type';

    public function typeColumn(): array
    {
        return [
            'attribute' => 'type',
            'visible' => $this->hasVisibleTypes(),
            'contentAttributes' => ['class' => 'text-nowrap'],
            'content' => function ($model) {
                $route = $this->getRoute($model);
                return $route ? Html::a($model->getTypeName(), $route) : $model->getTypeName();
            },
        ];
    }

    public function typeIconColumn(): array
    {
        return [
            'visible' => $this->hasVisibleTypes(),
            'contentAttributes' => ['class' => 'text-center'],
            'content' => function ($model) {
                $icon = $this->getTypeIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    public function getTypeDropdown(): ?FilterDropdown
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
