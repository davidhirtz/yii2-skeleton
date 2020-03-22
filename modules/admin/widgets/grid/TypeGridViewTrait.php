<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
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
     * @return array
     */
    public function typeColumn()
    {
        return [
            'attribute' => 'type',
            'visible' => !$this->type && count($this->getModel()::getTypes()) > 1,
            'content' => function ($model) {
                /** @var TypeAttributeTrait $model */
                return Html::a($model->getTypeName(), $this->getRoute($model));
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
            'paramName' => 'type',
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
                'url' => Url::current(['type' => $id, 'page' => null]),
            ];
        }

        return $items;
    }
}