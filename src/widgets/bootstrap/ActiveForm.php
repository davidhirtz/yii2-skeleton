<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\traits\ActiveFormTrait;

class ActiveForm extends \yii\widgets\ActiveForm
{
    use ActiveFormTrait;

    public $enableClientScript = false;
    public $fieldClass = ActiveField::class;
    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'form-content',
            'field' => 'form-row',
            'label' => 'label',
            'offset' => 'form-content',
        ],
    ];
    public string $layout = 'horizontal';
}
