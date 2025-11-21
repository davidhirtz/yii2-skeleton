<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\CheckboxField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Stringable;
use Yii;

/**
 * @property LoginForm $model
 */
class LoginActiveForm extends ActiveForm
{
    public string $layout = "{rows}{buttons}";

    protected function renderContent(): string|Stringable
    {
        $this->attributes['id'] ??= 'login-form';
        $this->attributes['hx-select'] ??= "main";

        $this->rows ??= [
            $this->getEmailField(),
            $this->getPasswordField(),
            $this->getRememberMeField(),
        ];

        return parent::renderContent();
    }

    protected function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('email')
            ->autocomplete('username')
            ->autofocus(!$this->model->hasErrors())
            ->prepend(Icon::make()
                ->name('envelope'))
            ->placeholder()
            ->type('email');
    }

    protected function getPasswordField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('password')
            ->prepend(Icon::make()
                ->name('key'))
            ->autocomplete('current-password')
            ->placeholder()
            ->type('password');
    }

    protected function getRememberMeField(): ?Stringable
    {
        return Yii::$app->getUser()->enableAutoLogin
            ? CheckboxField::make()
                ->model($this->model)
                ->property('rememberMe')
            : null;
    }
}
