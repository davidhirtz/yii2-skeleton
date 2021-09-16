<?php

namespace davidhirtz\yii2\skeleton\i18n;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class I18N.
 * @package davidhirtz\yii2\skeleton\i18n
 *
 * @property array $languages
 * @see \davidhirtz\yii2\skeleton\i18n\I18N::getLanguages()
 */
class I18N extends \yii\i18n\I18N
{
    /**
     * @var array
     */
    public static $languageLabels = [
        'de' => 'Deutsch',
        'en-US' => 'English',
    ];

    /**
     * @var array
     * @see getLanguages()
     */
    private $_languages;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!isset($this->translations['skeleton'])) {
            $this->translations['skeleton'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => Yii::$app->sourceLanguage,
                'basePath' => '@skeleton/messages',
            ];
        }

        parent::init();
    }

    /**
     * @return array|null
     */
    public function getLanguages()
    {
        if ($this->_languages === null) {
            $this->_languages = [Yii::$app->language];
        }

        return $this->_languages;
    }

    /**
     * @param array|string $languages
     */
    public function setLanguages($languages)
    {
        $this->_languages = array_unique((array)$languages);
    }

    /**
     * @param string $language
     * @return mixed
     */
    public function getLabel($language)
    {
        return ArrayHelper::getValue(static::$languageLabels, $language);
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        return substr(Yii::$app->language, 0, 2);
    }

    /**
     * Returns the translated attribute name for given language. If language is omitted
     * the current application language is used.
     *
     * @param string $attribute
     * @param string $language
     * @return string
     */
    public function getAttributeName($attribute, $language = null)
    {
        if (!$language) {
            $language = Yii::$app->language;
        }

        return rtrim($attribute . '_' . ($language != Yii::$app->sourceLanguage ? strtr('-', '_', mb_strtolower($language, Yii::$app->charset)) : ''), '_');
    }

    /**
     * Returns a flat array with all translated attribute name for given languages.
     * If languages is omitted all available languages are used.
     *
     * @param array|string $attributes
     * @param array $languages
     * @return array
     */
    public function getAttributeNames($attributes, $languages = null)
    {
        $names = [];

        if ($languages === null) {
            $languages = $this->getLanguages();
        }

        foreach ((array)$attributes as $attribute) {
            foreach ($languages as $language) {
                $names[] = $this->getAttributeName($attribute, $language);
            }
        }

        return $names;
    }

    /**
     * @param string $tableName
     * @param string $language
     * @return string
     */
    public function getTableName($tableName, $language = null)
    {
        return '{{%' . $this->getAttributeName($tableName, $language) . '}}';
    }
}