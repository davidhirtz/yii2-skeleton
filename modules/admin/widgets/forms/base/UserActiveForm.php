<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\widgets\forms\CountryDropdown;
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
    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'status',
                'name',
                ['email', 'email'],
                ['newPassword', 'password'],
                ['repeatPassword', 'password'],
                'oldPassword',
                '-',
                ['language', LanguageDropdown::class],
                ['timezone', TimezoneDropdown::class],
                ['upload', 'fileInput'],
                '-',
                'first_name',
                'last_name',
                'city',
                ['country', CountryDropdown::class],
                'sendEmail',
            ];
        }

        parent::init();
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function oldPasswordField($options = [])
    {
        return $this->model->password ? $this->field($this->model, 'oldPassword', ['enableClientValidation' => false])->passwordInput($options) : '';
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function statusField($options = [])
    {
        if ($this->model->isOwner()) {
            return null;
        }

        $statusOptions = ArrayHelper::getColumn(User::getStatuses(), 'name');
        return count($statusOptions) > 1 ? $this->field($this->model, 'status')->dropDownList($statusOptions, $options) : '';
    }

    /**
     * @param array $options
     * @return \yii\widgets\ActiveField|string
     */
    public function sendEmailField($options = [])
    {
        return $this->model->getIsNewRecord() ? $this->field($this->model, 'sendEmail')->checkbox($options) : '';
    }
}