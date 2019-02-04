<?php

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class ActiveForm.
 * @package davidhirtz\yii2\skeleton\widgets\bootstrap
 *
 * @method static ActiveForm begin($config = [])
 * @method ActiveField field($model, $attribute, $options = [])
 */
class ActiveForm extends \yii\bootstrap4\ActiveForm
{
    use ActiveFormTrait;

    /**
     * @inheritdoc
     */
    public $fieldConfig = [
        'horizontalCssClasses' => [
            'wrapper' => 'col-md-8 col-lg-6',
            'field' => 'form-group row',
            'label' => 'col-form-label col-md-4',
            'offset' => 'offset-md-4 col-md-8 col-lg-6',
        ],
    ];

    /**
     * @inheritdoc
     */
    public $layout = 'horizontal';

    /**
     * @var string
     */
    public $fieldClass = 'davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField';

    /**
     * @inheritdoc
     */
    public $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
}