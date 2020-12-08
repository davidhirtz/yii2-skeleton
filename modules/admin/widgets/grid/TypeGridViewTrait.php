<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\helpers\Url;

/**
 * Trait TypeGridViewTrait
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid
 *
 * @method TypeAttributeTrait getModel()
 */
trait TypeGridViewTrait
{
    /**
     * @var int
     */
    public $type;

    /**
     * @var string the type parameter name
     */
    public $typeParamName = 'type';

    /**
     * @var string|null whether the default item in the types dropdown should be shown
     */
    public $defaultTypeItem = null;

    /**
     * @return array
     */
    public function typeColumn()
    {
        return [
            'attribute' => 'type',
            'visible' => !$this->type && count($this->getModel()::getTypes()) > 1,
            'content' => function ($model) {
                /** @var ActiveRecord|TypeAttributeTrait $model */
                return ($route = $this->getRoute($model)) ? Html::a($model->getTypeName(), $route) : $model->getTypeName();
            }
        ];
    }

    /**
     * @return array
     */
    public function typeIconColumn()
    {
        return [
            'visible' => !$this->type && count($this->getModel()::getTypes()) > 1,
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
                /** @var ActiveRecord|TypeAttributeTrait $model */
                $icon = $this->getTypeIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    /**
     * @return string
     */
    public function typeDropdown()
    {
        $type = $this->getModel()::getTypes()[$this->type] ?? false;

        return ButtonDropdown::widget([
            'label' => $type ? Html::tag('strong', $type['plural'] ?? $type['name']) : Yii::t('skeleton', 'Type'),
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

        foreach ($this->getModel()::getTypes() as $id => $type) {
            $items[] = [
                'label' => $type['plural'] ?? $type['name'],
                'url' => Url::current([$this->typeParamName => $id, 'page' => null]),
            ];
        }

        return $items;
    }

    /**
     * @param TypeAttributeTrait $model
     * @return Icon
     */
    protected function getTypeIcon($model)
    {
        return Icon::tag($model->getTypeIcon(), [
            'data-toggle' => 'tooltip',
            'title' => $model->getTypeName(),
        ]);
    }
}