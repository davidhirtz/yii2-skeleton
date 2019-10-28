<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\LanguageDropdown;
use davidhirtz\yii2\skeleton\widgets\forms\TimezoneDropdown;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;

/**
 * Class UserActiveForm.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base
 *
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    public $fields = [
        ['status'],
        ['name'],
        ['email', 'email'],
        ['newPassword', 'password'],
        ['repeatPassword', 'password'],
        ['oldPassword'],
        ['-'],
        ['language', LanguageDropdown::class],
        ['timezone', TimezoneDropdown::class],
        ['-'],
        ['first_name'],
        ['last_name', 'text'],
        ['city', 'text'],
        ['country'],
        ['sendEmail', 'checkbox'],
    ];

    /**
     * @param array $options
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function oldPasswordField($options = [])
    {
        return $this->model->password ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options) : null;
    }

    /**
     * @param array $options
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function statusField($options = [])
    {
        if ($this->model->isOwner()) {
            return null;
        }

        $statusOptions = ArrayHelper::getColumn(User::getStatuses(), 'name');
        return count($statusOptions) > 1 ? $this->field($this->model, 'status')->dropDownList($statusOptions, $options) : null;
    }
}