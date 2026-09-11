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
     * @var array<string, string> alias per "attribute/language"
     */
    private array $_translationJoins = [];

    /**
     * @var list<string>|null `null` until decided
     */
    private ?array $_translationLanguages = null;

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
                "[[$alias]].[[model_class]] = :{$alias}_model_class"
                . " AND [[$alias]].[[model_id]] = {$this->getTableAlias()}.[[id]]"
                . " AND [[$alias]].[[language]] = :{$alias}_language"
                . " AND [[$alias]].[[attribute]] = :{$alias}_attribute",
                [
                    ":{$alias}_model_class" => $instance->getTranslationModelClass(),
                    ":{$alias}_language" => $language,
                    ":{$alias}_attribute" => $attribute,
                ]
            );

            $this->_translationJoins[$key] = $alias;
        }

        return $this->_translationJoins[$key];
    }

    /**
     * Applied by {@see static::populate()} for every list unless {@see static::withoutTranslations()} was called, so
     * a list read in another language later does not query per record. A single record stays lazy.
     *
     * @param list<string>|string|null $languages defaults to every configured language
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

    public function withoutTranslations(): static
    {
        $this->_translationLanguages = [];
        unset($this->with['translations']);

        return $this;
    }

    /**
     * Rewrites a translated attribute name, bare or prefixed, to its stored expression, so `Sort` and `orderBy()` need
     * no knowledge of it.
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
     * The parent resolves the relations, so deciding here covers `all()`, `each()` and `batch()` alike.
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
        $this->select = $this->prefixColumns($this->getModelInstance()->getColumnAttributes());
        return $this;
    }
}
