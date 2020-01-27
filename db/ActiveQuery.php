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
     * Adds columns with model prefix.
     *
     * @param array $columns
     * @return $this
     */
    public function addSelectPrefixed($columns)
    {
        if (!is_array($this->select)) {
            $this->select = $this->normalizeSelect($this->select);
        }

        foreach ($this->select as $key => $attribute) {
            if (in_array($attribute, $columns)) {
                unset($this->select[$key]);
            }
        }

        foreach ($columns as $column) {
            $this->select[] = $this->getModelInstance()::tableName() . ".[[{$column}]]";
        }

        return $this;
    }

    /**
     * @return $this
     * @todo this is not working for prefixed columns.
     */
    public function replaceI18nAttributes()
    {
        if (is_array($this->select)) {
            if ($attributes = $this->getModelInstance()->i18nAttributes) {
                $i18n = Yii::$app->getI18n();
                foreach ($this->select as &$attribute) {
                    if (in_array($attribute, $attributes)) {
                        $attribute = $i18n->getAttributeName($attribute);
                    }
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
     * @param int $status
     * @return $this
     */
    public function whereStatus($status = null)
    {
        if ($status !== null) {
            static::$_status = (int)$status;
        }

        $model = $this->getModelInstance();
        return $this->andFilterWhere(['>=', $model::tableName() . '.status', static::$_status]);
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
    public function sanitizeSearchString($search)
    {
        return trim(strtr($search, ['%' => '']));
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