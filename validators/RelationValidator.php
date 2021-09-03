<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
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
     * @var string the relation name
     */
    public $relation;

    /**
     * @var bool whether validator should skip on empty. This defaults to `false` to typecast value and generate
     * an error if `required` is `true`.
     */
    public $skipOnEmpty = false;

    /**
     * @var bool whether an error should be added when `attribute` is empty.
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
     * @param ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $columnSchema = $model::getTableSchema()->getColumn($attribute);
        $value = $columnSchema->phpTypecast($model->getAttribute($attribute));
        $model->setAttribute($attribute, $value);

        if ($model->isAttributeChanged($attribute)) {
            if ($value) {
                /** @var ActiveRecord $record */
                $related = $model->{$this->relation};

                if ((!$related || $related->getPrimaryKey() !== $value) && !$model->refreshRelation($this->relation)) {
                    $model->addInvalidAttributeError($attribute);
                }
            } else {
                if ($this->required || $model->isAttributeRequired($attribute)) {
                    $model->addInvalidAttributeError($attribute);
                }

                $model->populateRelation($this->relation, null);
            }
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