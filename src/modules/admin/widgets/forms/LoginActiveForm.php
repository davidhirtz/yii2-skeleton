<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;
use yii\widgets\ActiveField;

class LoginActiveForm extends ActiveForm
{
    public ?LoginForm $model = null;
    public $enableClientValidation = false;

    public function init(): void
    {
        $this->id = $this->getId(false) ?? 'login-form';
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
        echo $this->emailField();
        echo $this->passwordField();
        echo $this->rememberMeField();
        echo $this->loginButton();
    }

    public function emailField(): ActiveField|string
    {
        $field = $this->field($this->model, 'email', [
            'icon' => 'envelope',
            'enableError' => false,
        ]);

        return $field->textInput([
            'autocomplete' => 'username',
            'autofocus' => !$this->model->hasErrors(),
            'type' => 'email',
        ]);
    }

    public function loginButton(): string
    {
        $button = Html::submitButton(Yii::t('skeleton', 'Login'), [
            'class' => 'btn btn-primary btn-block',
        ]);

        return Html::tag('div', $button, [
            'class' => 'form-group',
        ]);
    }

    public function passwordField(): ActiveField|string
    {
        $field = $this->field($this->model, 'password', [
            'icon' => 'key',
            'enableError' => false,
        ]);

        return $field->passwordInput([
            'autocomplete' => 'current-password',
        ]);
    }

    public function rememberMeField(): ActiveField|string
    {
        return Yii::$app->getUser()->enableAutoLogin
            ? $this->field($this->model, 'rememberMe')->checkbox()
            : '';
    }
}