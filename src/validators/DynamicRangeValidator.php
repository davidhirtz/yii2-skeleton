<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\validators\RangeValidator;

class DynamicRangeValidator extends RangeValidator
{
    /**
     * @var bool whether numeric values should be cast to integer if validation was successful.
     */
    public bool $integerOnly = true;

    /**
     * @var array which will be dynamically overridden by {@see DynamicRangeValidator::getDynamicRange()}. Defaults to
     * an empty array to prevent an exception thrown by {@see RangeValidator}.
     */
    public $range = [];

    public function validateAttribute($model, $attribute): void
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        parent::validateAttribute($model, $attribute);

        if ($this->integerOnly && !$model->hasErrors($attribute)) {
            $model->{$attribute} = is_numeric($value = $model->{$attribute}) ? (int)$value : $value;
        }
    }

    public function clientValidateAttribute($model, $attribute, $view): null|string
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        return parent::clientValidateAttribute($model, $attribute, $view);
    }

    public function getDynamicRange(Model $model, string $attribute): array
    {
        $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));

        if (!$model->hasMethod($method)) {
            throw new InvalidConfigException($model::class . '::' . $method . '() must be defined to use ' . self::class . '.');
        }

        return array_keys($model->{$method}());
    }
}
