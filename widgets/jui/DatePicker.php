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
     * @var bool
     */
    public $constrainInput = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        if ($this->dateFormat === null) {
            $this->dateFormat = 'short';
        }

        if (!$this->language) {
            $this->language = Yii::$app->language;
        }

        if (!isset($this->clientOptions['dateFormat'])) {
            $this->clientOptions['dateFormat'] = strncmp($this->dateFormat, 'php:', 4) === 0 ?
                FormatConverter::convertDatePhpToJui(substr($this->dateFormat, 4)) :
                FormatConverter::convertDateIcuToJui($this->dateFormat);
        }

        if (!$this->constrainInput) {
            $this->clientOptions['constrainInput'] = false;
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
                try {
                    if ($this->showTime) {
                        // jQuery UI Datepicker does not support datetime formats.
                        $date = Yii::$app->getFormatter()->asDate($value, $this->dateFormat);
                        $dateLength = strlen($date);
                        $time = substr(Yii::$app->getFormatter()->asDatetime($value, $this->dateFormat), $dateLength);
                        $value = $date . $time;

                        if ($this->showTime) {
                            // Unfortunately Datepicker's "instance.lastVal" is not updated when the widget is open, to improve the
                            // functionality the current date is cached via jQuery data and the time part of string is added on select.
                            $this->clientEvents['keyup'] = new JsExpression('function(e){$(this).data("value",$(this).val())}');
                            $this->clientOptions['onSelect'] = new JsExpression('function(t,i){$(this).val(t+($(this).data("value")||i.lastVal).substring(' . $dateLength . ')).focus()}');
                        }

                    } else {
                        $value = Yii::$app->getFormatter()->asDate($value, $this->dateFormat);
                    }
                } catch (\Exception $exception) {
                }
            }
        }

        $options = $this->options;
        $options['value'] = $value;

        if ($this->hasModel()) {
            echo Html::activeTextInput($this->model, $this->attribute, $options);
        } else {
            echo Html::textInput($this->name, $value, $options);
        }

        $this->clientEvents['keypress'] = new JsExpression('function(e){if(e.which==13)this.form.submit()}');

        $this->registerWidget('datepicker');
    }
}