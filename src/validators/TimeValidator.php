<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * @noinspection PhpUnused
 */

class TimeValidator extends Validator
{
    public string $pattern = '/^([01]?[0-9]|2[0-3]):?([0-5][0-9])\s?(am|pm)?$/';

    public function init(): void
    {
        if (!$this->pattern) {
            throw new InvalidConfigException('The "pattern" property must be set.');
        }

        $this->message ??= Yii::t('yii', '{attribute} is invalid.');

        parent::init();
    }

    public function validateAttribute($model, $attribute): void
    {
        // Removes trailing seconds
        $value = strlen((string)($value = $model->$attribute)) === 8 ? preg_replace('/:00$/', '', (string)$value) : $value;
        $strlen = strlen((string)$model->$attribute);

        if ($strlen === 1) {
            $value = "0{$value}00";
        } elseif ($strlen < 4) {
            $value = substr("{$value}00", 0, 4);
        }

        if (!preg_match($this->pattern, (string)$value, $match)) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        $hours = $match[1] ?? 0;
        $minutes = $match[2] ?? 0;

        if (ArrayHelper::getValue($match, 3) == 'pm' && $hours < 12) {
            $hours += 12;
        }

        // Adds seconds for MySQL conform data format
        $model->$attribute = "$hours:$minutes:00";
    }
}
