<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Actions\SaveTranslations;
use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;

/**
 * Stores the translated attributes (`name_de`) in {@see Translation} records: they stay attributes but have no column.
 * Requires {@see I18nAttributesTrait}.
 *
 * @property-read Translation[] $translations {@see static::getTranslations()}
 *
 * @mixin ActiveRecord
 */
trait TranslationTrait
{
    /**
     * @var array<string, array{string, string}>|null
     */
    private ?array $translatedAttributeNames = null;

    /**
     * @var list<string>
     */
    private array $translatedAttributeNamesKey = [];

    /**
     * @var list<string>
     */
    private array $loadedTranslationLanguages = [];

    /**
     * @return list<string>
     */
    public function getTranslationAttributes(): array
    {
        return array_values($this->i18nAttributes);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function getTranslatedAttributeNames(): array
    {
        $attributes = $this->getTranslationAttributes();

        if (!$attributes) {
            return [];
        }

        $languages = $this->getTranslationLanguages();
        $key = [...$attributes, ...$languages];

        // Keyed rather than built once: a shared instance() outlives both the attributes and the languages.
        if ($this->translatedAttributeNames === null || $this->translatedAttributeNamesKey !== $key) {
            $i18n = Yii::$app->getI18n();
            $this->translatedAttributeNames = [];
            $this->translatedAttributeNamesKey = $key;

            foreach ($attributes as $attribute) {
                foreach ($languages as $language) {
                    $this->translatedAttributeNames[$i18n->getAttributeName($attribute, $language)] = [
                        $attribute,
                        $language,
                    ];
                }
            }
        }

        return $this->translatedAttributeNames;
    }

    #[Override]
    public function __get($name)
    {
        if ($this->isUnloadedVirtualAttribute((string)$name)) {
            $this->populateVirtualAttributes((string)$name);
        }

        return parent::__get($name);
    }

    /**
     * Populates before the first write, so the old value is known to dirty tracking and the trail.
     */
    #[Override]
    public function __set($name, $value): void
    {
        if ($this->isUnloadedVirtualAttribute((string)$name)) {
            $this->populateVirtualAttributes((string)$name);
        }

        parent::__set($name, $value);
    }

    public function resetLoadedTranslations(): void
    {
        $this->loadedTranslationLanguages = [];
    }

    /**
     * @return TranslationQuery<Translation>
     */
    public function getTranslations(): TranslationQuery
    {
        /** @var TranslationQuery<Translation> */
        return $this->hasMany(Translation::class, ['model_id' => 'id'])
            ->andOnCondition([Translation::tableName() . '.[[model_class]]' => $this->getTranslationModelClass()]);
    }

    /**
     * @param list<string>|null $languages
     */
    public function populateTranslationAttributes(?array $languages = null): void
    {
        $languages ??= $this->getTranslationLanguages();

        if (!$this->isRelationPopulated('translations')) {
            $this->refreshRelation('translations');
        }

        $this->setTranslationAttributes($languages, $this->translations);
    }

    /**
     * @param list<string> $languages
     */
    public function markTranslationsLoaded(array $languages): void
    {
        $this->loadedTranslationLanguages = array_values(array_unique([
            ...$this->loadedTranslationLanguages,
            ...$languages,
        ]));
    }

    /**
     * @return array<string, string|null>
     */
    public function saveVirtualAttributes(): array
    {
        return (new SaveTranslations($this))->save();
    }

    public function deleteVirtualAttributes(): void
    {
        Translation::deleteAll([
            'model_class' => $this->getTranslationModelClass(),
            'model_id' => $this->getPrimaryKey(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function getTranslationLanguages(): array
    {
        $languages = Yii::$app->getI18n()->getLanguages();
        return array_values(array_diff($languages, [Yii::$app->sourceLanguage]));
    }

    protected function isUnloadedVirtualAttribute(string $name): bool
    {
        return !$this->getIsNewRecord()
            && in_array($name, $this->getVirtualAttributes(), true)
            && !$this->isVirtualAttributeLoaded($name);
    }

    protected function isVirtualAttributeLoaded(string $name): bool
    {
        $language = $this->getTranslatedAttributeNames()[$name][1] ?? null;
        return $language === null || in_array($language, $this->loadedTranslationLanguages, true);
    }

    protected function populateVirtualAttributes(string $name): void
    {
        $languages = array_values(array_diff($this->getTranslationLanguages(), $this->loadedTranslationLanguages));

        if (!$languages) {
            return;
        }

        $this->setTranslationAttributes($languages, $this->getTranslations()->whereLanguage($languages)->all());
        $this->markTranslationsLoaded($languages);
    }

    /**
     * @param list<string> $languages
     * @param Translation[] $translations
     */
    private function setTranslationAttributes(array $languages, array $translations): void
    {
        $values = [];

        foreach ($translations as $translation) {
            $values["$translation->language/$translation->attribute"] = $translation->value;
        }

        foreach ($this->getTranslatedAttributeNames() as $name => [$attribute, $language]) {
            if (in_array($language, $languages, true)) {
                $value = $values["$language/$attribute"] ?? null;

                $this->setAttribute($name, $value);
                $this->setOldAttribute($name, $value);
            }
        }
    }
}
