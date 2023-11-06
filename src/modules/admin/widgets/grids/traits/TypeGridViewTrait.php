<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Url;

/**
 * @method ActiveRecord getModel()
 */
trait TypeGridViewTrait
{
    public ?int $type = null;

    /**
     * @var string the type parameter name
     */
    public string $typeParamName = 'type';

    /**
     * @var string|null whether the default item in the types dropdown should be shown
     */
    public ?string $defaultTypeItem = null;

    public function typeColumn(): array
    {
        return [
            'attribute' => 'type',
            'contentOptions' => ['class' => 'text-nowrap'],
            'visible' => !$this->type && count($this->getModel()::getTypes()) > 1,
            'content' => fn($model) =>
                /** @var ActiveRecord|\davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait $model */
                ($route = $this->getRoute($model)) ? Html::a($model->getTypeName(), $route) : $model->getTypeName()
        ];
    }

    public function typeIconColumn(): array
    {
        return [
            'visible' => !$this->type && count($this->getModel()::getTypes()) > 1,
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
                /** @var ActiveRecord|\davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait $model */
                $icon = $this->getTypeIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    public function typeDropdown(): string
    {
        $typeOptions = $this->getModel()::getTypes()[$this->type] ?? false;

        if ($typeOptions) {
            $name = isset($typeOptions['class']) ? $this->getModel()::instantiate(['type' => $this->type])->getTypeName() : ($typeOptions['plural'] ?? $typeOptions['name']);
        }

        return ButtonDropdown::widget([
            'label' => $name ?? Yii::t('skeleton', 'Type'),
            'items' => $this->typeDropdownItems(),
            'paramName' => $this->typeParamName,
            'defaultItem' => $this->defaultTypeItem,
        ]);
    }

    /**
     * @return array
     */
    protected function typeDropdownItems(): array
    {
        $items = [];

        foreach ($this->getModel()::getTypes() as $type => $typeOptions) {
            $items[] = [
                'label' => isset($typeOptions['class']) ? $this->getModel()::instantiate(['type' => $type])->getTypeName() : ($typeOptions['plural'] ?? $typeOptions['name']),
                'url' => Url::current([$this->typeParamName => $type, 'page' => null]),
            ];
        }

        return $items;
    }

    protected function getTypeIcon(ActiveRecordInterface $model): Icon
    {
        /** @var \davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait $model */
        return Icon::tag($model->getTypeIcon(), [
            'data-toggle' => 'tooltip',
            'title' => $model->getTypeName(),
        ]);
    }
}