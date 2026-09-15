<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\I18n;

use Override;
use Yii;
use yii\i18n\PhpMessageSource;

/**
 * @property array $languages {@see I18N::getLanguages()}
 * @property list<string> $languages
 */
class I18N extends \yii\i18n\I18N
{
    /**
     * @var array<string, string>
     */
    public static array $languageLabels = [
        'de' => 'Deutsch',
        'en-US' => 'English',
        'fr' => 'Français',
        'pt' => 'Português',
    ];

    /**
     * @var list<string>
     */
    private array $languages;

    #[Override]
    public function init(): void
    {
        $this->translations['skeleton'] ??= [
            'class' => PhpMessageSource::class,
            'basePath' => '@skeleton/../messages',
            'forceTranslation' => true,
            'sourceLanguage' => Yii::$app->sourceLanguage,
        ];

        $this->translations['country'] ??= [
            'class' => PhpMessageSource::class,
            'basePath' => '@skeleton/../messages',
            'forceTranslation' => true,
            'sourceLanguage' => Yii::$app->sourceLanguage,
        ];

        $this->languages ??= [Yii::$app->language];

        parent::init();
    }

    /**
     * Calls the given callback with the given language set as application language.
     */
    public function callback(string $language, callable $callback): mixed
    {
        $original = Yii::$app->language;
        Yii::$app->language = $language;

        try {
            return $callback($language);
        } finally {
            Yii::$app->language = $original;
        }
    }

    /**
     * @return list<string>
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param list<string>|string $languages
     */
    public function setLanguages(array|string $languages): void
    {
        $this->languages = array_values(array_unique((array)$languages));
    }

    public function hasLanguage(string $language): bool
    {
        return in_array($language, $this->getLanguages(), true);
    }

    /**
     * A language with no entry in {@see I18N::$languageLabels} is rendered as its own code rather than as
     * nothing — every caller renders the result.
     */
    public function getLabel(string $language): string
    {
        return static::$languageLabels[$language] ?? $language;
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
     *
     * @param list<string>|string $attributes
     * @param list<string>|null $languages
     * @return list<string>
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
