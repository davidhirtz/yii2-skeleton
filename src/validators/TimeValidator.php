<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

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
        $value = (string)$model->$attribute;
        $value = strlen($value) === 8 ? preg_replace('/:00$/', '', $value) : $value;
        $strlen = strlen((string) $value);

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

        if (($match['3'] ?? false) == 'pm' && $hours < 12) {
            $hours += 12;
        }

        // Adds seconds for MySQL conform data format
        $model->$attribute = implode(':', [
            str_pad((string) $hours, 2, '0', STR_PAD_LEFT),
            str_pad((string) $minutes, 2, '0', STR_PAD_LEFT),
            '00',
        ]);
    }
}
