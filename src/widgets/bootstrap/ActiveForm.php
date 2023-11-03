<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;

/**
 * @method static ActiveForm begin($config = [])
 * @method ActiveField field($model, $attribute, $options = [])
 */
class ActiveForm extends \yii\bootstrap4\ActiveForm
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