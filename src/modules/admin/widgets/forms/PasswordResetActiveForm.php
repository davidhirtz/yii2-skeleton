<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\traits\EmailFieldTrait;
use Yii;
use yii\widgets\ActiveField;

class PasswordResetActiveForm extends ActiveForm
{
    use EmailFieldTrait;

    public $enableClientValidation = false;

    public function __construct(public PasswordResetForm $model, $config = [])
    {
        parent::__construct($config);
    }

    #[\Override]
    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'password-reset-form';
        parent::init();
    }

    #[\Override]
    public function run(): string
    {
        $this->renderFields();
        return parent::run();
    }

    public function renderFields(): void
    {
        echo $this->helpBlock();

        echo $this->emailField(['readonly' => true]);
        echo $this->newPasswordField();
        echo $this->repeatPasswordField();

        echo $this->resetPasswordButton();
    }

    public function newPasswordField(): ActiveField|string
    {
        return $this->field($this->model, 'newPassword', ['icon' => 'key'])->passwordInput([
            'autofocus' => !$this->model->hasErrors(),
        ]);
    }

    public function repeatPasswordField(): ActiveField|string
    {
        return $this->field($this->model, 'repeatPassword', ['icon' => 'key'])->passwordInput();
    }

    public function helpBlock(): string
    {
        $content = $this->model->user->password_hash
            ? Yii::t('skeleton', 'Please enter a new password below to update your account.')
            : Yii::t('skeleton', 'Please enter a password below to complete your account.');

        return Html::tag('p', $content);
    }

    public function resetPasswordButton(): string
    {
        return $this->submitButton(Yii::t('skeleton', 'Save New Password'));
    }
}
