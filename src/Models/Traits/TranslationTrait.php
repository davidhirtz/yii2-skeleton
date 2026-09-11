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
    private ?array $_translatedAttributeNames = null;

    /**
     * @var list<string>
     */
    private array $_translatedAttributeNamesKey = [];

    /**
     * @var list<string>
     */
    private array $_loadedTranslationLanguages = [];

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
        if ($this->_translatedAttributeNames === null || $this->_translatedAttributeNamesKey !== $key) {
            $i18n = Yii::$app->getI18n();
            $this->_translatedAttributeNames = [];
            $this->_translatedAttributeNamesKey = $key;

            foreach ($attributes as $attribute) {
                foreach ($languages as $language) {
                    $this->_translatedAttributeNames[$i18n->getAttributeName($attribute, $language)] = [
                        $attribute,
                        $language,
                    ];
                }
            }
        }

        return $this->_translatedAttributeNames;
    }

    /**
     * @return list<string>
     */
    public function getVirtualAttributes(): array
    {
        return array_keys($this->getTranslatedAttributeNames());
    }

    /**
     * @return list<string>
     */
    public function getColumnAttributes(): array
    {
        return array_values(array_diff($this->attributes(), $this->getVirtualAttributes()));
    }

    #[Override]
    public function attributes(): array
    {
        return array_values(array_unique([...parent::attributes(), ...$this->getVirtualAttributes()]));
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

    /**
     * A refresh sets every virtual attribute to `null`; the old values follow so none reads as changed.
     */
    #[Override]
    public function afterRefresh(): void
    {
        $this->_loadedTranslationLanguages = [];

        foreach ($this->getVirtualAttributes() as $name) {
            $this->setOldAttribute($name, $this->getAttribute($name));
        }

        parent::afterRefresh();
    }

    #[Override]
    protected function insertInternal($attributes = null): bool
    {
        return parent::insertInternal($this->filterColumnAttributes($attributes));
    }

    #[Override]
    protected function updateInternal($attributes = null): false|int
    {
        // A translation-only change touches no column, so Yii reports 0 affected rows even though a record was written.
        $virtual = $this->getDirtyAttributes($this->getVirtualAttributes());
        $result = parent::updateInternal($this->filterColumnAttributes($attributes));

        return $result === 0 && $virtual ? 1 : $result;
    }

    /**
     * @return TranslationQuery<Translation>
     */
    public function getTranslations(): TranslationQuery
    {
        /** @var TranslationQuery<Translation> */
        return $this->hasMany(Translation::class, ['model_id' => 'id'])
            ->andOnCondition([Translation::tableName() . '.[[model]]' => $this->getTranslationModelClass()]);
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
        $this->_loadedTranslationLanguages = array_values(array_unique([
            ...$this->_loadedTranslationLanguages,
            ...$languages,
        ]));
    }

    public function updateOldVirtualAttributes(): void
    {
        foreach ($this->getVirtualAttributes() as $name) {
            $this->setOldAttribute($name, $this->getAttribute($name));
        }

        $this->markTranslationsLoaded($this->getTranslationLanguages());
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
            'model' => $this->getTranslationModelClass(),
            'model_id' => $this->getPrimaryKey(),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function getTranslationLanguages(): array
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
        return $language === null || in_array($language, $this->_loadedTranslationLanguages, true);
    }

    protected function populateVirtualAttributes(string $name): void
    {
        $languages = array_values(array_diff($this->getTranslationLanguages(), $this->_loadedTranslationLanguages));

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

    /**
     * @param list<string>|null $attributes
     * @return list<string>
     */
    private function filterColumnAttributes(?array $attributes): array
    {
        return $attributes === null
            ? $this->getColumnAttributes()
            : array_values(array_diff($attributes, $this->getVirtualAttributes()));
    }
}
