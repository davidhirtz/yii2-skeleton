<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use Yii;
use yii\base\Model;
use yii\grid\DataColumn;
use yii\helpers\Html;

class CounterColumn extends DataColumn
{
    /**
     * @var array
     */
    public $headerOptions = ['class' => 'd-none d-md-table-cell text-center'];

    /**
     * @var array
     */
    public $contentOptions = ['class' => 'd-none d-md-table-cell text-center'];

    /**
     * @var callable|null
     */
    public $route;

    /**
     * @var array
     */
    public $numberFormatterOptions = [];

    /**
     * @var array
     */
    public $numberFormatterTextOptions = [];

    /**
     * @var string[]
     */
    public $countHtmlOptions = ['class' => 'badge'];

    /**
     * @var string
     */
    public $emptyValue = '';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->content) {
            $this->content = function (Model $model, $key, $index) {
                if (!($count = $this->getDataCellValue($model, $key, $index))) {
                    return $this->emptyValue;
                }

                $count = Yii::$app->getFormatter()->asInteger($count, $this->numberFormatterOptions, $this->numberFormatterTextOptions);
                $route = $this->route ? call_user_func($this->route, $model) : null;

                return $route ? Html::a($count, $route, $this->countHtmlOptions) : Html::tag('div', $count, $this->countHtmlOptions);
            };
        }

        parent::init();
    }
}