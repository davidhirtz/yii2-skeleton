<?php

namespace davidhirtz\yii2\skeleton\validators;

use yii\validators\RegularExpressionValidator;

class HexColorValidator extends RegularExpressionValidator
{
    public $pattern = '/^#?(?:[0-9a-fA-F]{3}){1,2}$/i';

    public function validateAttribute($model, $attribute): void
    {
        parent::validateAttribute($model, $attribute);

        if (!$model->hasErrors($attribute)) {
            $model->$attribute = ltrim((string) $model->$attribute, '#');
        }
    }
}
