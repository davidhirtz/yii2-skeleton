<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\helpers\Url;

/**
 * @method ActiveRecord getModel()
 */
trait StatusGridViewTrait
{
    /**
     * @var int
     */
    public $status;

    /**
     * @var string the status parameter name
     */
    public $statusParamName = 'status';

    /**
     * @var string|null whether the default item in the status dropdown should be shown
     */
    public $defaultStatusItem = null;

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
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
        $items = $this->statusDropdownItems();
        $active = $items[$this->status] ?? false;

        return ButtonDropdown::widget([
            'label' => $active ? Html::tag('strong', $active['label']) : Yii::t('skeleton', 'Status'),
            'items' => $items,
            'paramName' => $this->statusParamName,
            'defaultItem' => $this->defaultStatusItem,
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
     * Returns array index by `status`, containing `label` and `url` for {@see ButtonDropdown}. This method can be
     * overridden to use other items or add additional statuses to the dropdown.
     *
     * @return array
     */
    protected function statusDropdownItems(): array
    {
        $items = [];

        foreach ($this->getModel()::getStatuses() as $id => $status) {
            $items[$id] = [
                'label' => $status['plural'] ?? $status['name'],
                'url' => Url::current([$this->statusParamName => $id, 'page' => null]),
            ];
        }

        return $items;
    }

    /**
     * @param StatusAttributeTrait|null $model
     * @return array
     */
    protected function statusSelectionButtonItems($model = null): array
    {
        if (!$model) {
            $model = $this->getModel();
        }

        $items = [];

        foreach ($model::getStatuses() as $id => $status) {
            $items[] = [
                'label' => $status['name'],
                'url' => '#',
                'linkOptions' => [
                    'data-method' => 'post',
                    'data-form' => $this->getSelectionFormId(),
                    'data-params' => [Html::getInputName($model, 'status') => $id],
                ],
            ];
        }

        return $items;
    }
}