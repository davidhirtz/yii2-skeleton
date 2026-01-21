<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use yii\widgets\InputWidget;

class HexColorInputWidget extends InputWidget
{
    public string $template = '<div data-color class="input-group"><div class="input-group-prepend"><div class="input-group-text">{color}</div></div>{input}</div>';

    public function init(): void
    {
        $this->options['value'] ??= $this->value ?: null;

        if ($this->hasModel()) {
            $this->options['value'] ??= Html::getAttributeValue($this->model, $this->attribute);
        }

        if ($this->options['value'] && !str_starts_with((string)$this->options['value'], '#')) {
            $this->options['value'] = "#{$this->options['value']}";
        }

        parent::init();
    }

    public function run(): string
    {
        $id = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();
        $name = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;

        $value = $this->options['value'] ?: '#000000';

        if (strlen((string) $value) === 4) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }

        $colorInput = Html::input('color', $name, $value, [
            'id' => "$id-color",
            'class' => 'form-control',
            'required' => true,
        ]);

        return strtr($this->template, [
            '{color}' => $colorInput,
            '{input}' => $this->renderInputHtml('text'),
        ]);
    }
}
