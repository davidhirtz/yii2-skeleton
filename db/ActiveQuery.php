<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;

/**
 * Class ActiveQuery.
 * @package \davidhirtz\yii2\skeleton\db
 * @see ActiveRecord
 *
 * @method ActiveRecord[] all($db = null)
 * @method ActiveRecord one($db = null)
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * @var int
     */
    protected static $_status;

    /**
     * Makes sure only relations with any relational values are actually loaded, this gets rid of a lot of useless
     * `WHERE 0=1` queries, which should not be executed in the first place. If an attribute is set or the relation is
     * requested via a junction table, the query is executed.
     *
     * @see https://forum.yiiframework.com/t/question-about-activequery-findfor/134188
     */
    public function findFor($name, $model)
    {
        foreach ($this->link as $attribute) {
            if ($this->via || $model->$attribute) {
                return parent::findFor($name, $model);
            }
        }

        return $this->multiple ? [] : null;
    }

    /**
     * Selects all columns defined in {@link ActiveRecord::attributes()}.
     * @return $this
     */
    public function selectAllColumns()
    {
        $this->select = $this->prefixColumns($this->getModelInstance()->attributes());
        return $this;
    }

    /**
     * Prefixes given `columns` with the table alias.
     *
     * @param array $columns
     * @return array
     */
    public function prefixColumns($columns): array
    {
        list(, $alias) = $this->getTableNameAndAlias();

        foreach ($columns as &$column) {
            $column = "{$alias}.[[{$column}]]";
        }

        return $columns;
    }

    /**
     * @inheritDoc
     */
    public function prepare($builder)
    {
        // Override Yii2's default implementation of adding the anti-pattern `$alias.*` on empty select. This causes
        // problems with `sql_mode=only_full_group_by`.
        if (empty($this->select)) {
            $this->selectAllColumns();
        }

        return parent::prepare($builder);
    }

    /**
     * @return $this
     */
    public function replaceI18nAttributes()
    {
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            if (is_array($this->select)) {
                if ($attributes = $this->getModelInstance()->i18nAttributes) {
                    $attributes = array_combine($attributes, $this->prefixColumns($attributes));
                    list(, $alias) = $this->getTableNameAndAlias();
                    $i18n = Yii::$app->getI18n();

                    foreach ($this->select as $key => $column) {
                        $attribute = isset($attributes[$column]) ? $column : array_search($column, $attributes);

                        if ($attribute) {
                            $column = "{$alias}.[[" . $i18n->getAttributeName($attribute) . ']]';
                            $this->select[$column] = $column;
                            unset($this->select[$key]);
                        }
                    }

                    $this->select = array_unique($this->select);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function whereLower($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->andWhere(["LOWER({$attribute})" => mb_strtolower($value, Yii::$app->charset)]);
        }

        return $this;
    }

    /**
     * Alters where statement and sets static status that can be used by related queries.
     *
     * @param int|null $status
     * @return $this
     */
    public function whereStatus($status = null)
    {
        static::setStatus($status);

        $model = $this->getModelInstance();
        return $this->andFilterWhere(['>=', $model::tableName() . '.status', static::$_status]);
    }

    /**
     * @param int $status
     */
    public static function setStatus($status): void
    {
        if ($status !== null) {
            static::$_status = (int)$status;
        }
    }

    /**
     * @return $this
     */
    public function enabled()
    {
        return $this->whereStatus($this->getModelInstance()::STATUS_ENABLED);
    }

    /**
     * @param string $search
     * @return array
     */
    public function splitSearchString($search)
    {
        return array_filter(preg_split('/[\s,]+/', $this->sanitizeSearchString($search)));
    }

    /**
     * @param string $search
     * @return string
     */
    public function sanitizeSearchString($search): string
    {
        return $search ? trim(strtr($search, ['%' => ''])) : '';
    }

    /**
     * @return ActiveRecord
     */
    protected function getModelInstance()
    {
        /** @var ActiveRecord $model */
        $model = $this->modelClass;
        return $model::instance();
    }
}