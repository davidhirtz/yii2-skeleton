<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\widgets\InputWidget;

class DynamicRangeDropdown extends InputWidget
{
    /**
     * @var bool whether dropdowns should only be displayed with at least two options.
     */
    public bool $skipOnEmpty = true;

    /**
     * @var string the array key which should be used to display the option values on associative arrays.
     */
    public $name = 'name';

    public function init(): void
    {
        if (!$this->hasModel()) {
            throw new InvalidConfigException("Properties 'model' and 'attribute' must be specified.");
        }

        if (($this->options['class'] ?? null) == 'form-control') {
            $this->options['class'] = 'form-select';
        }

        parent::init();
    }

    public function run(): void
    {
        if (count($items = $this->getItems()) > 1 || !$this->skipOnEmpty || isset($this->options['prompt'])) {
            echo Html::activeDropDownList($this->model, $this->attribute, $items, $this->options);
        }
    }

    protected function getItems(): array
    {
        $method = 'get' . Inflector::camelize(Inflector::pluralize($this->attribute));

        if (!$this->model->hasMethod($method)) {
            throw new InvalidConfigException(($this->model !== null ? $this->model::class : self::class) . '::' . $method . '() must be defined to use ' . self::class . '.');
        }

        $options = $this->model->{$method}();
        return is_string(current($options)) ? $options : ArrayHelper::getColumn($this->model->{$method}(), $this->name);
    }
}
