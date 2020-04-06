<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\validators\Validator;
use Yii;

/**
 * Class TimeValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class TimeValidator extends Validator
{
    /**
     * @var string
     */
    public $pattern = '/^([01]?[0-9]|2[0-3]):?([0-5][0-9])\s?(am|pm)?$/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->pattern === null) {
            throw new InvalidConfigException('The "pattern" property must be set.');
        }

        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }

        parent::init();
    }

    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $strlen = strlen($value);

        if ($strlen === 1) {
            $value = "0{$value}00";
        } elseif ($strlen < 4) {
            $value = substr("{$value}00", 0, 4);
        }

        if (!preg_match($this->pattern, $value, $match)) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        $hours = $match[1] ?? 0;
        $minutes = $match[2] ?? 0;

        if (ArrayHelper::getValue($match, 3) == 'pm' && $hours < 12) {
            $hours += 12;
        }

        $model->$attribute = "{$hours}:{$minutes}:00";
    }
}