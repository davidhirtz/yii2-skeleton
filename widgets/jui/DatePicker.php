<?php

namespace davidhirtz\yii2\skeleton\widgets\jui;

use Yii;
use yii\helpers\FormatConverter;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * Class DatePicker
 * @package davidhirtz\yii2\skeleton\widgets\jui
 */
class DatePicker extends InputWidget
{
    use JuiWidgetTrait;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string|false
     */
    public $dateFormat;

    /**
     * @var bool
     */
    public $showTime = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        if ($this->dateFormat === null) {
            $this->dateFormat = $this->showTime ? 'php:Y-m-d H:i' : Yii::$app->getFormatter()->dateFormat;
        }

        if (!$this->language) {
            $this->language = Yii::$app->language;
        }

        if (strncmp($this->dateFormat, 'php:', 4) === 0) {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDatePhpToJui(substr($this->dateFormat, 4));
        } elseif ($this->dateFormat) {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDateIcuToJui($this->dateFormat, 'date', $this->language);
        }

        if ($this->showTime) {
            $this->clientOptions['onSelect'] = new JsExpression('function(t){$(this).val(t.slice(0, 10)+" 00:00");}');
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if ($value = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value) {
            if ($this->dateFormat) {
                $value = Yii::$app->formatter->asDate($value, $this->dateFormat);
            }
        }

        $options = $this->options;
        $options['value'] = $value;

        if ($this->hasModel()) {
            echo Html::activeTextInput($this->model, $this->attribute, $options);
        } else {
            echo Html::textInput($this->name, $value, $options);
        }

        $this->registerWidget('datepicker');
    }
}
