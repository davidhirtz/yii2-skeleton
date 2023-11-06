<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\assets\SignupAsset;
use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;
use yii\bootstrap4\ActiveField;
use yii\helpers\Url;

/**
 * SignupActiveForm is a widget that builds an interactive HTML form for {@see SignupForm}.
 */
class SignupActiveForm extends ActiveForm
{
    public ?SignupForm $model = null;

    public function init(): void
    {
        $this->registerSignupClientScript();
        parent::init();
    }

    public function run(): string
    {
        echo $this->usernameField();
        echo $this->emailField();
        echo $this->passwordField();
        echo $this->termsField();

        echo $this->honeypotField();
        echo $this->tokenField();
        echo $this->timeZoneField();

        echo $this->submitButton();

        return parent::run();
    }

    public function usernameField(): ActiveField|string
    {
        return $this->field($this->model, 'name', ['icon' => 'user'])->textInput([
            'autofocus' => !$this->model->hasErrors()
        ]);
    }

    public function emailField(): ActiveField|string
    {
        return $this->field($this->model, 'email', ['icon' => 'envelope'])->textInput([
            'autocomplete' => 'username',
            'type' => 'email',
        ]);
    }

    public function passwordField(): ActiveField|string
    {
        return $this->field($this->model, 'password', ['icon' => 'key'])->passwordInput([
            'autocomplete' => 'new-password',
        ]);
    }

    public function termsField(): ActiveField|string
    {
        return $this->field($this->model, 'terms', ['enableError' => false])->checkbox();
    }

    public function honeypotField(): string
    {
        return Html::activeHiddenInput($this->model, 'honeypot', ['id' => 'honeypot']);
    }

    /**
     * @see AccountController::actionToken()
     */
    public function tokenField(): string
    {
        return Html::activeHiddenInput($this->model, 'token', [
            'id' => 'token',
            'data-url' => Url::toRoute(['account/token']),
        ]);
    }

    public function timeZoneField(): string
    {
        return Html::activeHiddenInput($this->model, 'timezone', ['id' => 'tz']);
    }

    public function submitButton(): string
    {
        return Html::submitButton(Yii::t('skeleton', 'Create Account'), [
            'class' => 'btn btn-primary btn-block',
        ]);
    }

    public function registerSignupClientScript(): void
    {
        SignupAsset::register($view = $this->getView());
        $view->registerJs("jQuery('#$this->id').signupForm();");
    }
}