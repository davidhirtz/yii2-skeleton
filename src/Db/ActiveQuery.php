<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Closure;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Override;
use Yii;
use yii\base\InvalidCallException;
use yii\db\BaseActiveRecord;
use yii\db\Query;

/**
 * @template T of ActiveRecord
 * @extends \yii\db\ActiveQuery<T>
 *
 * @property class-string<T> $modelClass
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    protected static ?int $status = null;

    /**
     * @var array<string, array{query: \yii\db\ActiveQuery<covariant \yii\db\ActiveRecord>, prefix: string, populate: Closure}>
     */
    private array $joinedRecords = [];

    /**
     * @param class-string<T> $modelClass
     * @param array<string, mixed> $config
     */
    public function __construct(string $modelClass, array $config = [])
    {
        /** @var T $model */
        $model = Yii::createObject($modelClass);
        parent::__construct($model::class, $config);
    }

    /**
     * Skips a relation without any relational value, which would only run `WHERE 0=1`.
     *
     * @return array<int|string, T>|T|null
     */
    #[Override]
    public function findFor($name, $model): array|ActiveRecord|null
    {
        foreach ($this->link as $attribute) {
            if ($this->via || $model->$attribute) {
                return parent::findFor($name, $model);
            }
        }

        return $this->multiple ? [] : null;
    }

    /**
     * The relation's own `where` moves into the ON clause, so its columns have to be qualified.
     *
     * @param Closure(ActiveQuery<covariant ActiveRecord>): mixed|null $callback
     */
    public function selectWith(string $name, string $joinType = 'LEFT JOIN', ?Closure $callback = null): static
    {
        $relation = $this->getModelInstance()->getRelation($name);

        if (!$relation instanceof self || $relation->multiple || $relation->via) {
            throw new InvalidCallException("selectWith() needs a hasOne relation without a junction table, '$name' is not one.");
        }

        if ($callback) {
            $callback($relation);
        }

        [$table, $alias] = $relation->getTableNameAndAlias();
        $parentAlias = $this->quoteTableAlias($this->getTableAlias());
        $childAlias = $this->quoteTableAlias($alias);
        $on = [];

        foreach ($relation->link as $childColumn => $parentColumn) {
            $on[] = "$parentAlias.[[$parentColumn]] = $childAlias.[[$childColumn]]";
        }

        $on = implode(' AND ', $on);

        foreach ([$relation->on, $relation->where] as $condition) {
            if (!empty($condition)) {
                $on = ['and', $on, $condition];
            }
        }

        $this->join($joinType, $relation->from ?: $table, $on, $relation->params);
        $inverse = $relation->inverseOf;

        return $this->selectJoinedRecord(
            $name,
            $relation,
            $alias,
            function (ActiveRecord $model, ?\yii\db\ActiveRecord $record) use ($name, $inverse): void {
                $model->populateRelation($name, $record);

                if ($record && $inverse) {
                    $inverseRelation = $record->getRelation($inverse);
                    $multiple = $inverseRelation instanceof \yii\db\ActiveQuery && $inverseRelation->multiple;
                    $record->populateRelation($inverse, $multiple ? [$model] : $model);
                }
            }
        );
    }

    /**
     * A query with `asArray()` keeps the `<name>__<column>` columns and populates nothing.
     *
     * @template R of \yii\db\ActiveRecord
     * @param \yii\db\ActiveQuery<R> $query
     * @param Closure(T, R|null): void $populate
     */
    protected function selectJoinedRecord(string $name, \yii\db\ActiveQuery $query, string $alias, Closure $populate): static
    {
        if (empty($this->select)) {
            $this->selectAllColumns();
        }

        $alias = $this->quoteTableAlias($alias);
        $prefix = "{$name}__";
        $columns = [];

        foreach ($this->getColumnNames($query) as $column) {
            $columns[$prefix . $column] = "$alias.[[$column]]";
        }

        $this->addSelect($columns);
        $this->joinedRecords[$name] = ['query' => $query, 'prefix' => $prefix, 'populate' => $populate];

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int|string, T>
     */
    #[Override]
    public function populate($rows): array
    {
        $models = parent::populate($rows);

        if ($this->joinedRecords && $models && !$this->asArray) {
            $pairs = $this->pairModelsWithRows($models, $rows);

            foreach ($this->joinedRecords as ['query' => $query, 'prefix' => $prefix, 'populate' => $populate]) {
                $this->populateJoinedRecords($pairs, $query, $prefix, $populate);
            }
        }

        return $this->primaryModel ? $this->sortByViaRecords($models, $this->primaryModel) : $models;
    }

    /**
     * An eagerly loaded `via()` relation fills each model's bucket in the order of the second query, not the junction's.
     *
     * @param array<array-key, mixed> $primaryModels
     * @return array<array-key, T>
     */
    #[Override]
    public function populateRelation($name, &$primaryModels): array
    {
        $models = parent::populateRelation($name, $primaryModels);

        foreach ($primaryModels as $primaryModel) {
            if ($primaryModel instanceof BaseActiveRecord && $primaryModel->isRelationPopulated($name)) {
                $related = $primaryModel->getRelatedRecords()[$name];

                if (is_array($related)) {
                    $primaryModel->populateRelation($name, $this->sortByViaRecords($related, $primaryModel));
                }
            }
        }

        return $models;
    }

    /**
     * A lazily read `via()` relation selects by the junction rows it loaded first, but not in their order.
     *
     * @template TModel
     * @param array<int|string, TModel> $models
     * @return array<int|string, TModel>
     */
    private function sortByViaRecords(array $models, BaseActiveRecord $primaryModel): array
    {
        if ($this->orderBy || !$this->multiple || !is_array($this->via) || count($this->link) !== 1) {
            return $models;
        }

        $viaName = $this->via[0];

        if (!is_string($viaName) || !$primaryModel->isRelationPopulated($viaName)) {
            return $models;
        }

        $attribute = (string)array_key_first($this->link);
        $viaAttribute = $this->link[$attribute];
        $positions = [];

        foreach ((array)$primaryModel->getRelatedRecords()[$viaName] as $record) {
            $positions[$record[$viaAttribute]] ??= count($positions);
        }

        uasort($models, fn ($a, $b): int => ($positions[$a[$attribute]] ?? PHP_INT_MAX) <=> ($positions[$b[$attribute]] ?? PHP_INT_MAX));

        return $this->indexBy ? $models : array_values($models);
    }

    /**
     * The parent folds the rows a hasMany `joinWith()` duplicated, so a count mismatch matches by primary key instead
     * of by position, the first row of a model winning.
     *
     * @param array<array-key, T> $models
     * @param array<array-key, array<string, mixed>> $rows
     * @return list<array{T, array<string, mixed>}>
     */
    private function pairModelsWithRows(array $models, array $rows): array
    {
        if (count($models) === count($rows)) {
            return array_map(null, array_values($models), array_values($rows));
        }

        $keys = $this->modelClass::primaryKey();
        $modelsByKey = [];

        foreach ($models as $model) {
            $key = $this->getRowKey($model, $keys);

            if ($key !== null) {
                $modelsByKey[$key] = $model;
            }
        }

        $pairs = [];

        foreach ($rows as $row) {
            $key = $this->getRowKey($row, $keys);

            if ($key !== null && isset($modelsByKey[$key])) {
                $pairs[] = [$modelsByKey[$key], $row];
                unset($modelsByKey[$key]);
            }
        }

        return $pairs;
    }

    /**
     * @param list<array{T, array<string, mixed>}> $pairs
     * @param \yii\db\ActiveQuery<covariant \yii\db\ActiveRecord> $query
     */
    private function populateJoinedRecords(array $pairs, \yii\db\ActiveQuery $query, string $prefix, Closure $populate): void
    {
        $keys = $query->modelClass::primaryKey();
        $rows = [];
        $rowKeys = [];

        foreach ($pairs as $index => [, $row]) {
            $attributes = [];

            foreach ($row as $column => $value) {
                if (str_starts_with((string)$column, $prefix)) {
                    $attributes[substr((string)$column, strlen($prefix))] = $value;
                }
            }

            // A later `select()` dropped the prefixed columns: there is nothing to populate from.
            if (!$attributes) {
                return;
            }

            $key = $this->getRowKey($attributes, $keys);
            $rowKeys[$index] = $key;

            if ($key !== null) {
                $rows[$key] ??= $attributes;
            }
        }

        $records = $this->createJoinedRecords($query, array_values($rows));

        if (count($records) !== count($rows)) {
            return;
        }

        $records = array_combine(array_keys($rows), $records);

        foreach ($pairs as $index => [$model]) {
            $key = $rowKeys[$index];
            $populate($model, $key === null ? null : $records[$key]);
        }
    }

    /**
     * @param \yii\db\ActiveQuery<covariant \yii\db\ActiveRecord> $query
     * @param list<array<string, mixed>> $rows
     * @return list<\yii\db\ActiveRecord>
     */
    private function createJoinedRecords(\yii\db\ActiveQuery $query, array $rows): array
    {
        if (!$rows) {
            return [];
        }

        // The inverse relation would point at the relation's shared model instance; `selectWith()` sets it itself.
        $query = clone $query;
        $query->inverseOf = null; // @phpstan-ignore assign.propertyType (Yii types it string, its own default is null)
        $query->indexBy = null;
        $query->asArray = false;
        $query->join = null;

        /** @var list<\yii\db\ActiveRecord> */
        return array_values($query->populate($rows));
    }

    /**
     * @param array<string, mixed>|\yii\db\ActiveRecord $row
     * @param array<string> $keys
     */
    private function getRowKey(array|\yii\db\ActiveRecord $row, array $keys): ?string
    {
        $values = [];

        foreach ($keys as $key) {
            if (!isset($row[$key])) {
                return null;
            }

            $values[] = (string)$row[$key];
        }

        return implode("\0", $values);
    }

    /**
     * @param \yii\db\ActiveQuery<covariant \yii\db\ActiveRecord> $query
     * @return list<string>
     */
    private function getColumnNames(\yii\db\ActiveQuery $query): array
    {
        /** @var class-string<\yii\db\ActiveRecord> $class */
        $class = $query->modelClass;
        $model = $class::instance();

        return $model instanceof ActiveRecord
            ? $model->getColumnAttributes()
            : array_values($class::getTableSchema()->getColumnNames());
    }

    protected function quoteTableAlias(string $alias): string
    {
        return str_contains($alias, '{{') ? $alias : "{{{$alias}}}";
    }

    /**
     * The virtual attributes are left out: `attributes()` reports them, but they have no column to select.
     */
    public function selectAllColumns(): static
    {
        $this->select = $this->prefixColumns($this->getModelInstance()->getColumnAttributes());
        return $this;
    }

    /**
     * @param array<string> $columns
     * @return list<string>
     */
    public function prefixColumns(array $columns): array
    {
        $alias = $this->getTableAlias();

        return array_values(array_map(static fn (string $column): string => "$alias.[[$column]]", $columns));
    }

    /**
     * Never selects `$alias.*` on an empty select, which breaks `sql_mode=only_full_group_by`.
     */
    #[Override]
    public function prepare($builder): Query
    {
        if (empty($this->select)) {
            $this->selectAllColumns();
        }

        return parent::prepare($builder);
    }

    public function getTableAlias(): string
    {
        [, $alias] = $this->getTableNameAndAlias();
        return $alias;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function whereLower(array $attributes): static
    {
        foreach ($attributes as $attribute => $value) {
            $this->andWhere(["LOWER($attribute)" => mb_strtolower((string)$value, Yii::$app->charset)]);
        }

        return $this;
    }

    /**
     * Also sets the static status, which related queries read.
     */
    public function whereStatus(?int $status = null): static
    {
        static::setStatus($status);

        $model = $this->getModelInstance();
        return $this->andFilterWhere(['>=', $model::tableName() . '.status', static::$status]);
    }

    /**
     * `null` means "no opinion", so it never clears a status an earlier query in the same request already set.
     */
    public static function setStatus(?int $status): void
    {
        if ($status !== null) {
            static::$status = (int)$status;
        }
    }

    /**
     * The status belongs to the request (a draft host sets `STATUS_DRAFT`), so `ApplicationTrait` clears it for the
     * next one.
     */
    public static function resetStatus(): void
    {
        static::$status = null;
    }

    public function enabled(): static
    {
        return $this->whereStatus(StatusAttributeInterface::STATUS_ENABLED);
    }

    /**
     * @return list<string>
     */
    public function splitSearchString(?string $search): array
    {
        return array_values(array_filter(preg_split('/[\s,]+/', $this->sanitizeSearchString($search)) ?: []));
    }

    public function sanitizeSearchString(?string $search): string
    {
        return $search ? trim(strtr($search, ['%' => ''])) : '';
    }

    /**
     * `ActiveRecord` is not generic, so `ActiveRecord<T>` resolved to `ActiveRecord&iterable<T>` and hid every
     * method the concrete model adds.
     *
     * @return T
     */
    protected function getModelInstance(): ActiveRecord
    {
        return $this->modelClass::instance();
    }
}
