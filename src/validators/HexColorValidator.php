<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\validators;

use Yii;
use yii\validators\StringValidator;

class HexColorValidator extends StringValidator
{
    public string $pattern = '/^#(?:[0-9a-fA-F]{3}){1,2}$/i';

    #[\Override]
    public function init(): void
    {
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }

        parent::init();
    }

    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $value = (string)$model->$attribute;

        if (!str_starts_with($value, '#')) {
            $value = '#' . $value;
        }

        if ($this->validateValue($value) !== null) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        $model->$attribute = $value;
    }

    #[\Override]
    protected function validateValue($value): ?array
    {
        $valid = !is_array($value) && preg_match($this->pattern, (string)$value);
        return $valid ? null : [$this->message, []];
    }
}
