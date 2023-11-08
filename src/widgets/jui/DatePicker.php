<?php

namespace davidhirtz\yii2\skeleton\widgets\jui;

use DateTime;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\helpers\FormatConverter;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class DatePicker extends InputWidget
{
    use JuiWidgetTrait;

    public string|null $language = null;
    public string|false|null $dateFormat = null;
    public bool $showTime = false;
    public bool $constrainInput = false;

    public function init(): void
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        $this->dateFormat ??= Yii::$app->language == 'de' ? 'd.m.Y' : 'Y-m-d';
        $this->language ??= Yii::$app->language;

        if (!isset($this->clientOptions['dateFormat'])) {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDatePhpToJui($this->dateFormat);
        }

        if (!$this->constrainInput) {
            $this->clientOptions['constrainInput'] = false;
        }

        parent::init();
    }

    public function run(): string
    {
        $date = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value;
        $options = $this->options;

        if ($date instanceof DateTime) {
            $options['value'] = $date->format($this->dateFormat);

            if ($this->showTime) {
                // jQuery UI Datepicker does not support datetime formats.
                $dateLength = strlen((string)$options['value']);
                $options['value'] .= $date->format(' H:i');

                // Unfortunately, Datepicker's "instance.lastVal" is not updated when the widget is open, to improve the
                // functionality the current date is cached via jQuery data, and the time part of string is added on select.
                $this->clientEvents['keyup'] = new JsExpression('function(e){$(this).data("value",$(this).val())}');
                $this->clientOptions['onSelect'] = new JsExpression('function(t,i){$(this).val(t+($(this).data("value")||i.lastVal).substring(' . $dateLength . ')).focus()}');
            }
        }

        $this->clientEvents['keypress'] = new JsExpression('function(e){if(e.which==13)this.form.submit()}');
        $this->registerWidget('datepicker');

        return $this->hasModel() ? Html::activeTextInput($this->model, $this->attribute, $options) : Html::textInput($this->name, $this->value, $options);
    }
}
