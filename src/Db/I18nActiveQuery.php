<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;
use yii\base\InvalidCallException;

/**
 * @template T of ActiveRecord
 * @extends ActiveQuery<T>
 */
class I18nActiveQuery extends ActiveQuery
{
    /**
     * @var array<string, string> the join alias per `"attribute/language"`
     */
    private array $_translationJoins = [];

    /**
     * @var list<string>|null the languages the translation records are eager loaded for; `null` until decided, which
     * {@see static::populate()} resolves to every configured language for a list
     */
    private ?array $_translationLanguages = null;

    /**
     * The column expression an attribute is read from in the given language. A translated attribute resolves to the
     * joined {@see Translation} value, with `$fallback` wrapped in a `COALESCE` on the source column, so a row without
     * a translation still sorts and matches by its source-language value.
     */
    public function getI18nAttributeName(string $attribute, ?string $language = null, bool $fallback = false): string
    {
        $instance = $this->getModelInstance();
        $alias = $this->getTableAlias();

        if (!$instance instanceof I18nAttributeInterface || !$instance->isI18nAttribute($attribute)) {
            return "$alias.[[$attribute]]";
        }

        $language ??= Yii::$app->language;

        if ($language === Yii::$app->sourceLanguage) {
            return "$alias.[[$attribute]]";
        }

        if (!$instance instanceof TranslationInterface || !in_array($attribute, $instance->getTranslationAttributes(), true)) {
            return "$alias.[[" . $instance->getI18nAttributeName($attribute, $language) . ']]';
        }

        $join = $this->joinTranslation($attribute, $language);

        return $fallback
            ? "COALESCE(NULLIF([[$join]].[[value]], ''), $alias.[[$attribute]])"
            : "[[$join]].[[value]]";
    }

    /**
     * Joins the {@see Translation} record of one attribute and language, once per query.
     *
     * @return string the join alias
     */
    public function joinTranslation(string $attribute, ?string $language = null): string
    {
        $instance = $this->getModelInstance();

        if (!$instance instanceof TranslationInterface) {
            throw new InvalidCallException($instance::class . ' does not store translations.');
        }

        $language ??= Yii::$app->language;
        $key = "$attribute/$language";

        if (!isset($this->_translationJoins[$key])) {
            $alias = 't_' . $attribute . '_' . strtr(mb_strtolower($language, Yii::$app->charset), '-', '_');
            $table = Translation::tableName();

            $this->leftJoin(
                "$table $alias",
                "[[$alias]].[[model]] = :{$alias}_model"
                . " AND [[$alias]].[[model_id]] = {$this->getTableAlias()}.[[id]]"
                . " AND [[$alias]].[[language]] = :{$alias}_language"
                . " AND [[$alias]].[[attribute]] = :{$alias}_attribute",
                [
                    ":{$alias}_model" => $instance->getTranslationModelClass(),
                    ":{$alias}_language" => $language,
                    ":{$alias}_attribute" => $attribute,
                ]
            );

            $this->_translationJoins[$key] = $alias;
        }

        return $this->_translationJoins[$key];
    }

    /**
     * Eager loads the {@see Translation} records of the given languages — every configured language by default — so a
     * listing reads every row's translated attributes without a query per row. A query that returns more than one row
     * applies this on its own unless {@see static::withoutTranslations()} was called: a list that is read in another
     * language later, such as a cached collection or a sitemap, would otherwise query once per record. A single
     * record stays lazy. The source language is not stored and is removed from the list.
     *
     * @param list<string>|string|null $languages
     */
    public function withTranslations(array|string|null $languages = null): static
    {
        $languages ??= Yii::$app->getI18n()->getLanguages();
        $languages = array_values(array_diff((array)$languages, [Yii::$app->sourceLanguage]));

        $this->_translationLanguages = $this->getModelInstance() instanceof TranslationInterface ? $languages : [];

        if (!$this->_translationLanguages) {
            return $this;
        }

        return $this->with([
            'translations' => fn (TranslationQuery $query) => $query->whereLanguage($languages),
        ]);
    }

    /**
     * Leaves the translations to the lazy load on first access, for a list whose translated attributes are not read.
     */
    public function withoutTranslations(): static
    {
        $this->_translationLanguages = [];
        unset($this->with['translations']);

        return $this;
    }

    /**
     * Reorders the given columns, replacing a translated attribute name — bare or prefixed — with the expression it is
     * stored as. This is what makes {@see \yii\data\Sort} and a plain `orderBy(['name_de' => SORT_ASC])` work without
     * the caller knowing where the value lives.
     */
    #[Override]
    protected function normalizeOrderBy($columns): array
    {
        $columns = parent::normalizeOrderBy($columns);
        $instance = $this->getModelInstance();

        if (!$instance instanceof TranslationInterface) {
            return $columns;
        }

        $names = $instance->getTranslatedAttributeNames();
        $result = [];

        foreach ($columns as $column => $direction) {
            $name = is_string($column) ? $this->getTranslatedAttributeName($column, $names) : null;

            if ($name !== null) {
                [$attribute, $language] = $names[$name];
                $column = $this->getI18nAttributeName($attribute, $language, fallback: true);
            }

            $result[$column] = $direction;
        }

        return $result;
    }

    /**
     * @param array<string, array{string, string}> $names
     */
    private function getTranslatedAttributeName(string $column, array $names): ?string
    {
        $name = trim((string)substr($column, (int)strrpos($column, '.')), '.[]{}%');
        return isset($names[$name]) ? $name : null;
    }

    /**
     * The relations are resolved inside the parent call, so deciding on the eager load here covers `all()`, `each()`
     * and `batch()` alike, while `one()` — a single row — is left to the lazy load.
     */
    #[Override]
    public function populate($rows): array
    {
        if ($this->_translationLanguages === null && count($rows) > 1) {
            $this->withTranslations();
        }

        $models = parent::populate($rows);

        if (!$this->_translationLanguages || $this->asArray) {
            return $models;
        }

        foreach ($models as $model) {
            if ($model instanceof TranslationInterface) {
                $model->populateTranslationAttributes($this->_translationLanguages);
                $model->markTranslationsLoaded($this->_translationLanguages);
            }
        }

        return $models;
    }

    #[Override]
    public function selectAllColumns(): static
    {
        $instance = $this->getModelInstance();

        if (!$instance instanceof TranslationInterface) {
            return parent::selectAllColumns();
        }

        $this->select = $this->prefixColumns($instance->getColumnAttributes());
        return $this;
    }
}
