<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\I18n;

use Hirtz\Skeleton\Web\UrlManager;
use Override;
use Yii;
use yii\i18n\PhpMessageSource;

/**
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
     * Also switches the URL manager's content language, which the admin sets apart from the application's.
     *
     * @template T
     * @param callable(string): T $callback
     * @return T
     */
    public function callback(string $language, callable $callback): mixed
    {
        $manager = $this->getUrlManager();
        $original = Yii::$app->language;
        $contentLanguage = $manager->getContentLanguage();

        Yii::$app->language = $language;
        $manager->setContentLanguage($contentLanguage !== null ? $language : null);

        try {
            return $callback($language);
        } finally {
            Yii::$app->language = $original;
            $manager->setContentLanguage($contentLanguage);
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

    public function getLanguageCode(?string $language = null): string
    {
        return substr($language ?? (string)Yii::$app->language, 0, 2);
    }

    /**
     * The URL manager's content language, the application's on the frontend. The admin can be in a language the
     * content is not kept in, which then reads the source language.
     */
    public function getContentLanguage(?string $language = null): string
    {
        $language ??= $this->getUrlManager()->getContentLanguage() ?? (string)Yii::$app->language;
        return in_array($language, $this->getLanguages(), true) ? $language : (string)Yii::$app->sourceLanguage;
    }

    public function getAttributeName(string $attribute, ?string $language = null): string
    {
        $language = $language ?: $this->getContentLanguage();

        return rtrim($attribute . '_' . ($language !== Yii::$app->sourceLanguage ? strtr(mb_strtolower((string)$language, Yii::$app->charset), '-', '_') : ''), '_');
    }

    /**
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

    private function getUrlManager(): UrlManager
    {
        return Yii::$app->getUrlManager();
    }
}
