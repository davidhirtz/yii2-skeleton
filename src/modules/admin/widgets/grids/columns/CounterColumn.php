<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use Yii;
use yii\base\Model;

class CounterColumn extends LinkDataColumn
{
    public $headerOptions = [
        'class' => 'd-none d-md-table-cell text-center',
    ];

    public $contentOptions = [
        'class' => 'd-none d-md-table-cell text-center',
    ];

    /**
     * @var array {@see Formatter::$numberFormatterOptions}
     */
    public array $numberFormatterOptions = [];

    /**
     * @var array {@see Formatter::$numberFormatterTextOptions}
     */
    public array $numberFormatterTextOptions = [];

    /**
     * @var array contains the HTML attributes for the link or wrapper
     */
    public array $wrapperOptions = ['class' => 'badge'];

    /**
     * @var string the value to be displayed when the count is 0 or not set
     */
    public string $emptyValue = '';

    public function init(): void
    {
        if (!is_callable($this->content)) {
            $this->content = function (Model $model, mixed $key, int $index) {
                $count = $this->getDataCellValue($model, $key, $index);

                if (!$count) {
                    return $this->emptyValue;
                }

                return Yii::$app->getFormatter()->asInteger($count, $this->numberFormatterOptions, $this->numberFormatterTextOptions);
            };
        }

        parent::init();
    }
}
