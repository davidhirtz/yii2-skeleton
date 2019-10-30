<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use Yii;

/**
 * Class ActiveFieldTrait.
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
trait ActiveFieldTrait
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
    public function slug($options = [])
    {
        $baseUrl = rtrim($options['baseUrl'] ?? Yii::$app->getRequest()->getHostInfo(), '/') . '/';
        unset($options['baseUrl']);

        return $this->prependInput($baseUrl)->input('text', $options);
    }
}