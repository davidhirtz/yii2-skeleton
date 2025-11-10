<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\FilterDropdown;
use Yii;
use yii\base\Model;

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
            'contentOptions' => ['class' => 'text-nowrap'],
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
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
                $icon = $this->getTypeIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    public function typeDropdown(): ?FilterDropdown
    {
        return $this->hasVisibleTypes()
            ? new FilterDropdown(
                $this->typeDropdownItems(),
                Yii::t('skeleton', 'Type'),
                $this->typeParamName,
                $this->typeDefaultItem,
            )
            : null;
    }

    protected function typeDropdownItems(): array
    {
        /** @var Model&TypeAttributeInterface $model */
        $model = $this->getModel();

        return array_map(fn ($model) => $model->getTypePlural(), $model::getTypeInstances());
    }

    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        return Icon::tag($model->getTypeIcon())
            ->tooltip($model->getTypeName())
            ->render();
    }

    protected function hasVisibleTypes(): bool
    {
        /** @var Model&TypeAttributeInterface $model */
        $model = $this->getModel();
        return !$this->type && count($model::getTypes()) > 1;
    }
}
