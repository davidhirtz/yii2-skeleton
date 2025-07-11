<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use Yii;
use yii\db\Query;

/**
 * @template T of ActiveRecord
 * @template-extends  \yii\db\ActiveQuery<T>
 *
 * @property class-string<T> $modelClass
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * @var int|null the global status to be used in WHERE clause with `whereStatus()`.
     */
    protected static ?int $_status = null;

    /**
     * Makes sure the container instantiates the model class before calling parent constructor.
     * Not sure why this is not part of the framework.
     * @param class-string<T> $modelClass
     */
    public function __construct(string $modelClass, array $config = [])
    {
        $modelClass = Yii::createObject($modelClass)::class;
        parent::__construct($modelClass, $config);
    }

    /**
     * Makes sure only relations with any relational values are actually loaded. This gets rid of a lot of useless
     * `WHERE 0=1` queries, which should not be executed in the first place. If an attribute is set or the relation is
     * requested via a junction table, the query is executed.
     *
     * @link https://forum.yiiframework.com/t/question-about-activequery-findfor/134188
     */
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
     * Selects all columns defined in {@see ActiveRecord::attributes()}.
     */
    public function selectAllColumns(): static
    {
        $this->select = $this->prefixColumns($this->getModelInstance()->attributes());
        return $this;
    }

    /**
     * Prefixes given `columns` with the table alias.
     */
    public function prefixColumns(array $columns): array
    {
        $alias = $this->getTableAlias();

        foreach ($columns as &$column) {
            $column = "$alias.[[$column]]";
        }

        return $columns;
    }

    /**
     * Override Yii2's default implementation of adding the anti-pattern `$alias.*` on empty select. This causes
     * problems with `sql_mode=only_full_group_by`.
     */
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

    public function whereLower(array $attributes): static
    {
        foreach ($attributes as $attribute => $value) {
            $this->andWhere(["LOWER($attribute)" => mb_strtolower((string)$value, Yii::$app->charset)]);
        }

        return $this;
    }

    /**
     * Alters WHERE clause and sets static status that can be used by related queries.
     */
    public function whereStatus(?int $status = null): static
    {
        static::setStatus($status);

        $model = $this->getModelInstance();
        return $this->andFilterWhere(['>=', $model::tableName() . '.status', static::$_status]);
    }

    public static function setStatus(?int $status): void
    {
        if ($status !== null) {
            static::$_status = (int)$status;
        }
    }

    public function enabled(): static
    {
        return $this->whereStatus(StatusAttributeInterface::STATUS_ENABLED);
    }

    public function splitSearchString(?string $search): array
    {
        return array_filter(preg_split('/[\s,]+/', $this->sanitizeSearchString($search)));
    }

    public function sanitizeSearchString(?string $search): string
    {
        return $search ? trim(strtr($search, ['%' => ''])) : '';
    }

    /**
     * @return ActiveRecord<T>
     */
    protected function getModelInstance(): ActiveRecord
    {
        return $this->modelClass::instance();
    }
}
