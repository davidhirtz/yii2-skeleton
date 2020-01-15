<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\validators\Validator;

/**
 * Class RelationValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class RelationValidator extends Validator
{
    /**
     * @var string
     */
    public $relation;

    /**
     * @var bool
     */
    public $skipOnEmpty = false;

    /**
     * @var bool
     */
    public $required = false;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->relation) {
            throw new InvalidConfigException('The "relation" property must be set.');
        }

        parent::init();
    }

    /**
     * Typecasts attribute and validates relation.
     *
     * @param \davidhirtz\yii2\skeleton\db\ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $columnSchema = $model::getTableSchema()->getColumn($attribute);
        $model->setAttribute($attribute, $value = $columnSchema->phpTypecast($model->getAttribute($attribute)));

        if ($value) {
            /** @var \yii\db\ActiveRecord $record */
            $related = $model->{$this->relation};
            if ((!$related || $related->getPrimaryKey() !== $value) && !$model->refreshRelation($this->relation)) {
                $model->addInvalidAttributeError($attribute);
            }
        } else {
            if ($this->required) {
                $model->addInvalidAttributeError($attribute);
            }

            $model->populateRelation($this->relation, null);
        }
    }

    /**
     * @inheritDoc
     */
    public function validate($value, &$error = null)
    {
        throw new NotSupportedException(get_class($this) . ' does not support validate().');
    }
}