<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\PasswordRecoverForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\EmailFieldTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\SubmitButtonTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;

class PasswordRecoverFormActiveForm extends ActiveForm
{
    use EmailFieldTrait;
    use SubmitButtonTrait;

    public ?PasswordRecoverForm $model = null;

    public $enableClientValidation = false;

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'password-recover-form';
        $this->model ??= Yii::createObject(PasswordRecoverForm::class);

        parent::init();
    }

    public function run(): string
    {
        $this->renderFields();
        return parent::run();
    }

    public function renderFields(): void
    {
        echo $this->helpBlock();
        echo $this->emailField();
        echo $this->sendEmailButton();
    }

    public function helpBlock(): string
    {
        $content = Yii::t('skeleton', 'Enter your email address and we will send you instructions how to reset your password.');
        return Html::tag('p', $content);
    }

    public function sendEmailButton(): string
    {
        return $this->submitButton(Yii::t('skeleton', 'Send Email'));
    }
}
