<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

class ActiveForm extends \yii\bootstrap4\ActiveForm
{
    public $fieldClass = ActiveField::class;
    public $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
}