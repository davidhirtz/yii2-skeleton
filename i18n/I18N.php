<?php

namespace davidhirtz\yii2\skeleton\i18n;

use Yii;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;

/**
 * Class I18N.
 * @package davidhirtz\yii2\skeleton\i18n
 *
 * @property array $languages {@see I18N::getLanguages()}
 */
class I18N extends \yii\i18n\I18N
{
    public static array $languageLabels = [
        'de' => 'Deutsch',
        'en-US' => 'English',
        'fr' => 'Français',
        'pt' => 'Português',
        'zh-CN' => '简体中文', //  // zh-HANS
        'zh-TW' => '繁體中文', // zh-HANT
    ];

    private ?array $_languages = null;

    public function init(): void
    {
        if (!isset($this->translations['skeleton'])) {
            $this->translations['skeleton'] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => Yii::$app->sourceLanguage,
                'basePath' => '@skeleton/messages',
            ];
        }

        parent::init();
    }

    /**
     * Calls the given callback with the given language set as application language.
     */
    public function callback(string $language, callable $callback): mixed
    {
        $prevLanguage = Yii::$app->language;
        Yii::$app->language = $language;

        $result = call_user_func($callback);

        Yii::$app->language = $prevLanguage;
        return $result;
    }

    public function getLanguages(): array
    {
        if ($this->_languages === null) {
            $this->_languages = [Yii::$app->language];
        }

        return $this->_languages;
    }

    /** @noinspection PhpUnused */
    public function setLanguages(array|string $languages): void
    {
        $this->_languages = array_unique((array)$languages);
    }

    public function getLabel(string $language): mixed
    {
        return ArrayHelper::getValue(static::$languageLabels, $language);
    }

    public function getLanguageCode(): string
    {
        return substr(Yii::$app->language, 0, 2);
    }

    /**
     * Returns the translated attribute name for given language. If the language is omitted, the current application
     * language is used.
     */
    public function getAttributeName(string $attribute, ?string $language = null): string
    {
        if (!$language) {
            $language = Yii::$app->language;
        }

        return rtrim($attribute . '_' . ($language != Yii::$app->sourceLanguage ? strtr(mb_strtolower($language, Yii::$app->charset), '-', '_') : ''), '_');
    }

    /**
     * Returns a flat array with all translated attribute names for given languages. If languages are omitted, all
     * available languages are used.
     */
    public function getAttributeNames(array|string $attributes, ?array $languages = null): array
    {
        $languages ??= $this->getLanguages();
        $names = [];

        foreach ((array)$attributes as $attribute) {
            foreach ($languages as $language) {
                $names[] = $this->getAttributeName($attribute, $language);
            }
        }

        return $names;
    }

    public function getTableName(string $tableName, ?string $language = null): string
    {
        return '{{%' . $this->getAttributeName($tableName, $language) . '}}';
    }
}