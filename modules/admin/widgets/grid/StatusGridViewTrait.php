<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\helpers\Url;

/**
 * Trait StatusGridViewTrait
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid
 *
 * @method StatusAttributeTrait getModel()
 */
trait StatusGridViewTrait
{
    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $statusParamName = 'status';

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
                /** @var ActiveRecord|StatusAttributeTrait $model */
                $icon = $this->getStatusIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    /**
     * @return string
     */
    public function statusDropdown()
    {
        $status = $this->getModel()::getStatuses()[$this->status] ?? false;

        return ButtonDropdown::widget([
            'label' => $status ? Html::tag('strong', $status['plural'] ?? $status['name']) : Yii::t('skeleton', 'Status'),
            'items' => $this->statusDropdownItems(),
            'paramName' => $this->statusParamName,
        ]);
    }

    /**
     * @param StatusAttributeTrait $model
     * @return Icon
     */
    protected function getStatusIcon($model)
    {
        return Icon::tag($model->getStatusIcon(), [
            'data-toggle' => 'tooltip',
            'title' => $model->getStatusName()
        ]);
    }

    /**
     * @return array
     */
    protected function statusDropdownItems(): array
    {
        $items = [];

        foreach ($this->getModel()::getStatuses() as $id => $status) {
            $items[] = [
                'label' => $status['plural'] ?? $status['name'],
                'url' => Url::current([$this->statusParamName => $id, 'page' => null]),
            ];
        }

        return $items;
    }
}