<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\assets\SignupAsset;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use Yii;
use yii\bootstrap4\ActiveField;

/**
 * SignupActiveForm is a widget that builds an interactive HTML form for {@link SignupForm}.
 */
class SignupActiveForm extends ActiveForm
{
    /**
     * @var SignupForm
     */
    public $model;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->registerSignupClientScript();
        parent::init();
    }


    /**
     * @return string
     */
    public function run()
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

    /**
     * @return ActiveField
     */
    public function usernameField()
    {
        return $this->field($this->model, 'name', ['icon' => 'user'])->textInput([
            'autofocus' => !$this->model->hasErrors()
        ]);
    }

    /**
     * @return ActiveField
     */
    public function emailField()
    {
        return $this->field($this->model, 'email', ['icon' => 'envelope'])->textInput([
            'autocomplete' => 'username',
            'type' => 'email',
        ]);
    }

    /**
     * @return ActiveField
     */
    public function passwordField()
    {
        return $this->field($this->model, 'password', ['icon' => 'key'])->passwordInput([
            'autocomplete' => 'new-password',
        ]);
    }

    /**
     * @return ActiveField
     */
    public function termsField()
    {
        return $this->field($this->model, 'terms', ['enableError' => false])->checkbox();
    }

    /**
     * @return string
     */
    public function honeypotField()
    {
        return Html::activeHiddenInput($this->model, 'honeypot', ['id' => 'honeypot']);
    }

    /**
     * @return string
     */
    public function tokenField()
    {
        return Html::activeHiddenInput($this->model, 'token', [
            'id' => 'token',
            'data-url' => Yii::$app->getUrlManager()->createUrl(['account/token']),
        ]);
    }

    /**
     * @return string
     */
    public function timeZoneField()
    {
        return Html::activeHiddenInput($this->model, 'timezone', ['id' => 'tz']);
    }

    /**
     * @return string
     */
    public function submitButton()
    {
        return Html::submitButton(Yii::t('skeleton', 'Create Account'), [
            'class' => 'btn btn-primary btn-block',
        ]);
    }

    /**
     * Registers the client script for the signup form.
     */
    public function registerSignupClientScript()
    {
        SignupAsset::register($view = $this->getView());
        $view->registerJs("jQuery('#$this->id').signupForm();");
    }
}