<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Override;
use Stringable;
use Yii;

/**
 * @property PasswordResetForm $model
 */
class PasswordResetActiveForm extends ActiveForm
{
    public array $attributes = ['class' => 'form-plain'];
    public array $excludedErrorProperties = ['newPassword', 'repeatPassword'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $this->attributes['id'] ??= $this->getId();

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
            $this->getNewPasswordField(),
            $this->getRepeatPasswordField(),
        ];

        $this->submitButtonText = Yii::t('skeleton', 'Save New Password');

        return parent::renderContent();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->content($this->model->user->password_hash
                ? Yii::t('skeleton', 'Please enter a new password below to update your account.')
                : Yii::t('skeleton', 'Please enter a password below to complete your account.'));
    }

    protected function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('email')
            ->disabled()
            ->prepend(Icon::make()
                ->name('envelope'))
            ->placeholder()
            ->type('email');
    }

    protected function getNewPasswordField(): ?Stringable
    {

        return InputField::make()
            ->model($this->model)
            ->property('newPassword')
            ->prepend(Icon::make()
                ->name('key'))
            ->autofocus(!$this->model->hasErrors())
            ->autocomplete('new-password')
            ->placeholder()
            ->type('password');
    }

    protected function getRepeatPasswordField(): ?Stringable
    {

        return InputField::make()
            ->model($this->model)
            ->property('repeatPassword')
            ->prepend(Icon::make()
                ->name('key'))
            ->autocomplete('repeat-password')
            ->placeholder()
            ->type('password');
    }
}
