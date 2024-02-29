<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserActiveFormTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class UserActiveForm extends ActiveForm
{
    use UserActiveFormTrait;

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

    public function statusField(array $options = []): ActiveField|string
    {
        $items = ArrayHelper::getColumn($this->model->user::getStatuses(), 'name');
        return $this->field($this->model, 'status', $options)->dropDownList($items);
    }

    public function sendEmailField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'sendEmail')->checkbox($options);
    }
}
