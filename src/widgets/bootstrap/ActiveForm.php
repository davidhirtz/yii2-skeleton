<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;

class ActiveForm extends \yii\bootstrap5\ActiveForm
{
    use ActiveFormTrait;

    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'col-form-content',
            'field' => 'form-group row',
            'label' => 'col-form-label',
            'offset' => 'col-form-content',
        ],
    ];

    public $layout = 'horizontal';
    public $fieldClass = ActiveField::class;
    public $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
}
