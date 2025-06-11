<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;

class ActiveForm extends \yii\widgets\ActiveForm
{
    use ActiveFormTrait;

    public $enableClientScript = false;
    public $fieldClass = ActiveField::class;
    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'col-form-content',
            'field' => 'form-group form-group-horizontal',
            'label' => 'form-label',
            'offset' => 'col-form-content',
        ],
    ];
    public string $layout = 'horizontal';
}
