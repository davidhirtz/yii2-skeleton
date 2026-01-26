<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\helpers\Url;

trait TypeGridViewTrait
{
    /**
     * @var int|null the current type
     */
    public ?int $type = null;

    /**
     * @var string the type parameter name
     */
    public string $typeParamName = 'type';

    /**
     * @var string|false|null whether the default item in the types dropdown should be shown
     */
    public string|false|null $typeDefaultItem = null;

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

    public function typeDropdown(): string
    {
        /** @var TypeAttributeInterface $model */
        // @phpstan-ignore varTag.nativeType
        $model = $this->getModel();
        $typeOptions = $this->type ? $model::getTypes()[$this->type] ?? null : null;

        if ($typeOptions) {
            $name = isset($typeOptions['class'])
                ? $model::instantiate(['type' => $this->type])->getTypeName()
                : ($typeOptions['plural'] ?? $typeOptions['name']);
        }

        return ButtonDropdown::widget([
            'label' => $name ?? Yii::t('skeleton', 'Type'),
            'items' => $this->typeDropdownItems(),
            'paramName' => $this->typeParamName,
            'defaultItem' => $this->typeDefaultItem,
        ]);
    }

    protected function typeDropdownItems(): array
    {
        /** @var TypeAttributeInterface $model */
        // @phpstan-ignore varTag.nativeType
        $model = $this->getModel();
        $items = [];

        foreach ($model::getTypes() as $type => $typeOptions) {
            $label = isset($typeOptions['class'])
                ? $model::instantiate(['type' => $type])->getTypeName()
                : ($typeOptions['plural'] ?? $typeOptions['name']);

            $items[$type] = [
                'label' => $label,
                'url' => Url::current([$this->typeParamName => $type, 'page' => null]),
            ];
        }

        return $items;
    }

    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        return (string)Icon::tag($model->getTypeIcon(), [
            'data-toggle' => 'tooltip',
            'title' => $model->getTypeName(),
        ]);
    }

    protected function hasVisibleTypes(): bool
    {
        /** @var TypeAttributeInterface $model */
        // @phpstan-ignore varTag.nativeType
        $model = $this->getModel();
        return !$this->type && count($model::getTypes()) > 1;
    }
}
