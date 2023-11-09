<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserFormTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\widgets\ActiveField;

/**
 * @property UserForm $model
 */
class AccountActiveForm extends ActiveForm
{
    use UserFormTrait;

    public bool $hasStickyButtons = true;

    /**
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
            'name',
            'email',
            'newPassword',
            'repeatPassword',
            '-',
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

    /**
     * @noinspection PhpUnused {@see static::$fields}
     */
    public function oldPasswordField(array $options = []): ActiveField|string
    {
        if (!$this->model->password_hash) {
            return '';
        }

        $options['enableClientValidation'] ??= false;

        $field = $this->field($this->model, 'oldPassword', $options)
            ->passwordInput();

        if ($icon = $this->getOldPasswordFieldIcon()) {
            $field->appendInput($icon);
        }

        return $field;
    }

    protected function getOldPasswordFieldIcon(array $options = []): string
    {
        return Icon::tag('info-circle', [
            'data-toggle' => 'tooltip',
            'title' => Yii::t('skeleton', 'Only needed, if you want to change your password'),
            ...$options,
        ]);
    }
}
