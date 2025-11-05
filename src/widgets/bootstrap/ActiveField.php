<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\HexColorInputWidget;
use Yii;

/**
 * @property ActiveForm $form
 */
class ActiveField extends \yii\bootstrap4\ActiveField
{
    /**
     * @var array|null containing a custom list of languages used for i18n-aware attributes.
     * Leave empty to use default languages.
     */
    public ?array $languages = null;

    /**
     * @var string input group with appended text.
     */
    public string $appendInputTemplate = '<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text">{append}</span></div></div>';

    /**
     * @var string input group with prepended text.
     */
    public string $prependInputTemplate = '<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">{prepend}</span></div>{input}</div>';

    public $checkTemplate = '{beginWrapper}<div class="form-check-inline">{input}{label}{error}{hint}</div>{endWrapper}';

    public function init(): void
    {
        $this->checkHorizontalTemplate = $this->checkTemplate;
        parent::init();
    }

    /**
     * Makes sure that empty input fields are not rendered. This only applies if the '{input}' was explicitly set to
     * an empty string (e.g., from widgets).
     */
    public function render($content = null): string
    {
        return ($content === null && ($this->parts['{input}'] ?? false) !== '')
            ? parent::render($content) :
            '';
    }

    public function checkbox($options = [], $enclosedByLabel = false): static
    {
        $this->labelOptions = []; // Removes label options, class can be removed when an extension is fixed...
        return parent::checkbox($options, $enclosedByLabel);
    }

    public function fileInput($options = []): static
    {
        $options['class'] ??= 'form-control-file';
        return parent::fileInput($options);
    }

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

    public function appendInput(string $text): static
    {
        $this->inputTemplate = strtr($this->appendInputTemplate, ['{append}' => $text]);
        return $this;
    }

    public function prependInput(string $text): static
    {
        $this->inputTemplate = strtr($this->prependInputTemplate, ['{prepend}' => $text]);
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
