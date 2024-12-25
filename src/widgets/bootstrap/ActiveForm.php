<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;

class ActiveForm extends \yii\bootstrap5\ActiveForm
{
    use ActiveFormTrait;

    public $enableClientScript = false;
    public $fieldClass = ActiveField::class;
    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'col-form-content',
            'field' => 'form-group row',
            'label' => 'col-form-label',
            'offset' => 'col-form-content',
        ],
    ];
    public $layout = 'horizontal';
}
