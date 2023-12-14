<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\LoginButtonTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;
use yii\widgets\ActiveField;

class GoogleAuthenticatorLoginActiveForm extends ActiveForm
{
    use LoginButtonTrait;

    public ?LoginForm $model = null;

    public $enableClientValidation = false;

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'google-authenticator-login-form';
        $this->model ??= Yii::createObject(LoginForm::class);

        parent::init();
    }

    public function run(): string
    {
        $this->renderFields();
        return parent::run();
    }

    public function renderFields(): void
    {
        echo $this->codeField();
        echo $this->emailField();
        echo $this->passwordField();
        echo $this->rememberMeField();
        echo $this->loginButton();
    }

    public function codeField(): ActiveField|string
    {
        $field = $this->field($this->model, 'code', [
            'icon' => 'qrcode',
            'enableError' => false,
        ]);

        return $field->textInput([
            'autocomplete' => 'one-time-code',
            'autofocus' => !$this->model->hasErrors(),
        ]);
    }

    public function emailField(): ActiveField|string
    {
        return Html::activeHiddenInput($this->model, 'email');
    }

    public function passwordField(): ActiveField|string
    {

        return Html::activeHiddenInput($this->model, 'password');
    }

    public function rememberMeField(): ActiveField|string
    {
        return Yii::$app->getUser()->enableAutoLogin
            ? Html::activeHiddenInput($this->model, 'rememberMe')
            : '';
    }
}