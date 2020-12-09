<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\widgets\InputWidget;

/**
 * Class DynamicRangeDropdown
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class DynamicRangeDropdown extends InputWidget
{
    /**
     * @var bool whether dropdowns should only be displayed with at least two options.
     */
    public $skipOnEmpty = true;

    /**
     * @var string the array key which should be used to display the option values on associative arrays.
     */
    public $name = 'name';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->hasModel()) {
            throw new InvalidConfigException("Properties 'model' and 'attribute' must be specified.");
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if (count($items = $this->getItems()) > 1 || !$this->skipOnEmpty) {
            echo Html::activeDropDownList($this->model, $this->attribute, $items, $this->options);
        }
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $method = 'get' . Inflector::camelize(Inflector::pluralize($this->attribute));

        if (!$this->model->hasMethod($method)) {
            throw new InvalidConfigException(get_class($this->model) . '::' . $method . '() must be defined to use ' . __CLASS__ . '.');
        }

        $options = $this->model->{$method}();
        return is_string(current($options)) ? $options : ArrayHelper::getColumn($this->model->{$method}(), $this->name);
    }
}