<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\forms\HexColorInputWidget;
use Yii;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

/**
 * @property ActiveForm $form
 */
class ActiveField extends \davidhirtz\yii2\skeleton\widgets\forms\ActiveField
{
    /**
     * @var array|null containing a custom list of languages used for i18n-aware attributes.
     * Leave empty to use default languages.
     */
    public ?array $languages = null;

    /**
     * @var string input group with appended text.
     */
    public string $appendInputTemplate = '<div class="input-group">{input}{append}</div>';

    /**
     * @var string input group with prepended text.
     */
    public string $prependInputTemplate = '<div class="input-group">{prepend}{input}</div>';

    public $checkTemplate = '{beginWrapper}<div class="form-check">{input}{label}</div>{error}{hint}{endWrapper}';

    public function init(): void
    {
        foreach ($this->model->getActiveValidators($this->attribute) as $validator) {
            if ($validator instanceof StringValidator) {
                $this->inputOptions['maxlength'] ??= $validator->max;
                break;
            }

            if ($validator instanceof NumberValidator) {
                $this->inputOptions['min'] ??= $validator->min;
                $this->inputOptions['max'] ??= $validator->max;
                break;
            }
        }

        $this->template = '<div class="col-form-label">{label}</div>{beginWrapper}{input}{error}{hint}{endWrapper}';
        $this->checkHorizontalTemplate = $this->checkTemplate;

        parent::init();
    }

    /**
     * Makes sure that empty input fields are not rendered. This only applies if the '{input}' was explicitly set to
     * an empty string (e.g., from widgets).
     */
    #[\Override]
    public function render($content = null): string
    {
        return ($content === null && ($this->parts['{input}'] ?? false) !== '')
            ? parent::render($content) :
            '';
    }

    #[\Override]
    public function checkbox($options = [], $enclosedByLabel = false): static
    {
        $this->labelOptions = []; // Removes label options, class can be removed when an extension is fixed...
        return parent::checkbox($options, $enclosedByLabel);
    }

    #[\Override]
    public function fileInput($options = []): static
    {
        $options['class'] ??= 'form-control-file';
        return parent::fileInput($options);
    }

    #[\Override]
    public function dropDownList($items, $options = []): static
    {
        if ($items || $this->model->isAttributeRequired($this->attribute)) {
            // The parent method has an incorrect method name, this can be changed to "dropDownList" once the parent
            // method is fixed.
            return parent::dropdownList($items, $options);
        }

        // Don't render an empty dropdown list.
        $this->parts['{input}'] = null;
        return $this;
    }

    public function appendInput(string $text, array $options = []): static
    {
        Html::addCssClass($options, 'input-group-append input-group-text');
        $content = Html::tag('div', $text, $options);

        $this->inputTemplate = strtr($this->appendInputTemplate, ['{append}' => $content]);
        return $this;
    }

    public function prependInput(string $text, array $options = []): static
    {
        Html::addCssClass($options, 'input-group-prepend input-group-text');
        $content = Html::tag('div', $text, $options);

        $this->inputTemplate = strtr($this->prependInputTemplate, ['{prepend}' => $content]);

        return $this;
    }

    public function hexColor(array $options = []): static
    {
        return $this->widget(HexColorInputWidget::class, $options);
    }

    public function slug(array $options = []): static
    {
        $baseUrl = $options['baseUrl'] ?? (rtrim((string)Yii::$app->getRequest()->getHostInfo(), '/') . '/');
        unset($options['baseUrl']);

        return $this->input('text', $options)->prependInput($baseUrl);
    }
}
