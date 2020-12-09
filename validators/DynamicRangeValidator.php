<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\validators\RangeValidator;

/**
 * Class DynamicRangeValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class DynamicRangeValidator extends RangeValidator
{
    /**
     * @var bool whether numeric values should be cast to integer if validation was successful.
     */
    public $integerOnly = true;

    /**
     * @var array which will be dynamically overridden by {@link DynamicRangeValidator::getDynamicRange()}. Defaults to
     * an empty array to prevent an exception thrown by {@link RangeValidator}.
     */
    public $range = [];

    /**
     * @inheritDoc
     */
    public function validateAttribute($model, $attribute)
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        parent::validateAttribute($model, $attribute);

        if ($this->integerOnly && !$model->hasErrors($attribute)) {
            $model->{$attribute} = is_numeric($value = $model->{$attribute}) ? (int)$value : $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        $this->range = $this->getDynamicRange($model, $attribute);
        return parent::clientValidateAttribute($model, $attribute, $view);
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @return array
     * @throws InvalidConfigException
     */
    public function getDynamicRange($model, $attribute)
    {
        $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));

        if (!$model->hasMethod($method)) {
            throw new InvalidConfigException(get_class($model) . '::' . $method . '() must be defined to use ' . __CLASS__ . '.');
        }

        return array_keys($model->{$method}());
    }
}