<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Hirtz\Skeleton\Db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use yii\validators\Validator;

class RelationValidator extends Validator
{
    /**
     * Guessed from the attribute name when empty.
     */
    public ?string $relation = null;

    /**
     * Off, so an empty value is still typecast and `required` can add its error.
     *
     * @var bool
     */
    public $skipOnEmpty = false;

    public bool $required = false;

    /**
     * @param ActiveRecord $model
     * @param string $attribute
     */
    #[\Override]
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

        if ($model->getIsNewRecord() || $model->isAttributeChanged($attribute)) {
            if ($value) {
                /** @var ActiveRecord|null $related */
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

    #[\Override]
    public function validate($value, &$error = null): bool
    {
        throw new NotSupportedException(static::class . ' does not support validate().');
    }
}
