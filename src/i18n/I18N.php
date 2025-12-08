<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\i18n;

use Yii;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;

/**
 * @property array $languages {@see I18N::getLanguages()}
 */
class I18N extends \yii\i18n\I18N
{
    public static array $languageLabels = [
        'de' => 'Deutsch',
        'en-US' => 'English',
        'fr' => 'Français',
        'pt' => 'Português',
        'ru' => 'Russian',
        'zh-CN' => '简体中文', //  // zh-HANS
        'zh-TW' => '繁體中文', // zh-HANT
    ];

    private array $_languages;

    #[\Override]
    public function init(): void
    {
        $this->translations['skeleton'] ??= [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => Yii::$app->sourceLanguage,
            'basePath' => '@skeleton/messages',
        ];

        $this->_languages ??= [Yii::$app->language];

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
        return $this->_languages;
    }

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
        return substr((string)Yii::$app->language, 0, 2);
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

        return rtrim($attribute . '_' . ($language !== Yii::$app->sourceLanguage ? strtr(mb_strtolower((string)$language, Yii::$app->charset), '-', '_') : ''), '_');
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
