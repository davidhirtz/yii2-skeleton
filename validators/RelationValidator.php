<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use yii\validators\Validator;

/**
 * Validates a models relation.
 */
class RelationValidator extends Validator
{
    /**
     * @var string|null the relation name, leave empty to guess from attribute name.
     */
    public ?string $relation = null;

    /**
     * @var bool whether validator should skip on empty. This defaults to `false` to typecast value and generate
     * an error if `required` is `true`.
     */
    public $skipOnEmpty = false;

    /**
     * @var bool whether an error should be added when `attribute` is empty.
     */
    public bool $required = false;

    /**
     * Typecasts attribute and validates relation.
     *
     * @param ActiveRecord $model
     * @param string $attribute
     * @throws InvalidConfigException
     */
    public function validateAttribute($model, $attribute): void
    {
        $relation = $this->relation ?? lcfirst(Inflector::camelize(str_replace('_id', '', $attribute)));

        if ($model->getRelation($relation, false) === null) {
            $className = $model::class;
            throw new InvalidConfigException("Relation $relation not found in $className.");
        }

        $columnSchema = $model::getTableSchema()->getColumn($attribute);
        $value = $columnSchema->phpTypecast($model->getAttribute($attribute));
        $model->setAttribute($attribute, $value);

        if ($model->isAttributeChanged($attribute)) {
            if ($value) {
                /** @var ActiveRecord $record */
                $related = $model->{$relation};

                if ((!$related || $related->getPrimaryKey() !== $value) && !$model->refreshRelation($relation)) {
                    $model->addInvalidAttributeError($attribute);
                }
            } else {
                if ($this->required || $model->isAttributeRequired($attribute)) {
                    $model->addInvalidAttributeError($attribute);
                }

                $model->populateRelation($relation, null);
            }
        }
    }

    /**
     * Not supported.
     */
    public function validate($value, &$error = null): bool
    {
        throw new NotSupportedException(static::class . ' does not support validate().');
    }
}