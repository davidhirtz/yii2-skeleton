<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use Yii;
use yii\base\Model;
use yii\grid\DataColumn;
use yii\helpers\Html;

class CounterColumn extends DataColumn
{
    public $headerOptions = ['class' => 'd-none d-md-table-cell text-center'];
    public $contentOptions = ['class' => 'd-none d-md-table-cell text-center'];

    /**
     * @var callable|null a callback function that returns the route for the count link
     */
    public mixed $route = null;

    /**
     * @var array {@see Formatter::$numberFormatterOptions}
     */
    public array $numberFormatterOptions = [];

    /**
     * @var array {@see Formatter::$numberFormatterTextOptions}
     */
    public array $numberFormatterTextOptions = [];

    /**
     * @var array contains the HTML attributes for the count link
     */
    public array $countHtmlOptions = ['class' => 'badge'];

    /**
     * @var string the value to be displayed when the count is 0
     */
    public string $emptyValue = '';

    public function init(): void
    {
        if (!is_callable($this->content)) {
            $this->content = function (Model $model, mixed $key, int $index) {
                if (!($count = $this->getDataCellValue($model, $key, $index))) {
                    return $this->emptyValue;
                }

                $count = Yii::$app->getFormatter()->asInteger($count, $this->numberFormatterOptions, $this->numberFormatterTextOptions);
                $route = is_callable($this->route) ? call_user_func($this->route, $model) : $this->route;

                return $route ? Html::a($count, $route, $this->countHtmlOptions) : Html::tag('div', $count, $this->countHtmlOptions);
            };
        }

        parent::init();
    }
}
