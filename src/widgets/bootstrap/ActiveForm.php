<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;

class ActiveForm extends \yii\bootstrap5\ActiveForm
{
    use ActiveFormTrait;

    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'col-md-8',
            'field' => 'form-group row',
            'label' => 'col-form-label col-md-3',
            'offset' => 'offset-md-3 col-md-8',
        ],
    ];

    public $layout = 'horizontal';
    public $fieldClass = ActiveField::class;
    public $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
}
