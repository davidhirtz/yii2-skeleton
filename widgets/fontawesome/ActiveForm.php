<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;

/**
 * Class ActiveField.
 * @package davidhirtz\yii2\skeleton\widgets\fontawesome
 */
class ActiveForm extends \yii\bootstrap4\ActiveForm
{
    use WidgetConfigTrait;

    /**
     * @var string
     */
    public $fieldClass = 'davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField';

    /**
     * @var string
     */
    public $validationStateOn = self::VALIDATION_STATE_ON_CONTAINER;
}