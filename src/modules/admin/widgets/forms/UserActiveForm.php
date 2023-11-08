<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserFormTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    use UserFormTrait;

    public bool $hasStickyButtons = true;

    /**
     * @uses static::statusField()
     * @uses static::emailField()
     * @uses static::newPasswordField()
     * @uses static::repeatPasswordField()
     * @uses static::languageField()
     * @uses static::timezoneField()
     * @uses static::uploadField()
     * @uses static::countryField()
     * @uses static::sendEmailField()
     */
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'name',
            'email',
            'newPassword',
            'repeatPassword',
            '-',
            'language',
            'timezone',
            'upload',
            '-',
            'first_name',
            'last_name',
            'city',
            'country',
            'sendEmail',
        ];

        parent::init();
    }

    public function sendEmailField(array $options = []): ActiveField|string
    {
        return $this->model->getIsNewRecord()
            ? $this->field($this->model, 'sendEmail')->checkbox($options)
            : '';
    }
}
