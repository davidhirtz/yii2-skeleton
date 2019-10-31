<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;


use Yii;

/**
 * Class ActiveField
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 */
class ActiveField extends \yii\bootstrap4\ActiveField
{
    /**
     * @var array containing a custom list of languages used for i18n aware attributes.
     * Leave empty to use default languages.
     */
    public $languages;

    /**
     * @var string input group with appended text.
     */
    public $appendInputTemplate = '<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text">{append}</span></div></div>';

    /**
     * @var string input group with prepended text.
     */
    public $prependInputTemplate = '<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">{prepend}</span></div>{input}</div>';

    /**
     * @var string
     */
    public $checkTemplate = '{beginWrapper}<div class="form-check-inline">{input}{label}{error}{hint}</div>{endWrapper}';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->checkHorizontalTemplate = $this->checkTemplate;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function checkbox($options = [], $enclosedByLabel = false)
    {
        $this->labelOptions = []; // Removes label options, class can be removed when extension is fixed...
        return parent::checkbox($options, $enclosedByLabel);
    }

    /**
     * @inheritdoc
     */
    public function fileInput($options = [])
    {
        if (!isset($options['class'])) {
            $options['class'] = 'form-control-file';
        }

        return parent::fileInput($options);
    }

    /**
     * @inheritdoc
     */
    public function dropdownList($items, $options = [])
    {
        if ($items || $this->model->isAttributeRequired($this->attribute)) {
            return parent::dropdownList($items, $options);
        }

        // Don't render empty drop down list.
        $this->parts['{input}'] = null;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function appendInput($text)
    {
        $this->inputTemplate = strtr($this->appendInputTemplate, ['{append}' => $text]);
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function prependInput($text)
    {
        $this->inputTemplate = strtr($this->prependInputTemplate, ['{prepend}' => $text]);
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function hexColor($options = [])
    {
        $options['maxlength'] = 6;
        return $this->input('text', $options)->prependInput('#');
    }

    /**
     * @param array $options
     * @return $this
     */
    public function slug($options = [])
    {
        $baseUrl = $options['baseUrl'] ?? (rtrim(Yii::$app->getRequest()->getHostInfo(), '/') . '/');
        unset($options['baseUrl']);

        return $this->input('text', $options)->prependInput($baseUrl);
    }
}