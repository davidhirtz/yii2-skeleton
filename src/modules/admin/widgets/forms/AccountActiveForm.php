<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserFormTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserFormTrait;

    public bool $hasStickyButtons = true;

    /**
     * @uses static::statusField()
     * @uses static::emailField()
     * @uses static::newPasswordField()
     * @uses static::repeatPasswordField()
     * @uses static::oldPasswordField()
     * @uses static::languageField()
     * @uses static::timezoneField()
     * @uses static::uploadField()
     * @uses static::countryField()
     */
    public function init(): void
    {
        $this->fields ??= [
            'status',
            'name',
            'email',
            'newPassword',
            'repeatPassword',
            'oldPassword',
            '-',
            'language',
            'timezone',
            'upload',
            '-',
            'first_name',
            'last_name',
            'city',
            'country',
        ];

        parent::init();
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function oldPasswordField(array $options = []): ActiveField|string
    {
        return $this->model->password_hash
            ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options)
            : '';
    }
}