<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;
use Yii;

/**
 * @property PasswordResetForm $model
 */
class PasswordResetActiveForm extends ActiveForm
{
    /**
     * @var array<string, mixed>
     */
    public array $attributes = ['class' => 'form-plain'];
    /**
     * @var list<string>
     */
    public array $excludedErrorProperties = ['newPassword', 'repeatPassword'];
    public bool $hasStickyButtons = false;
    protected string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'password-reset-form';

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
            $this->getNewPasswordField(),
            $this->getRepeatPasswordField(),
        ];

        $this->submitButtonText = Yii::t('skeleton', 'PASSWORD_RESET_ACTIVE_SAVE_NEW_PASSWORD');

        parent::configure();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->content($this->model->user->password_hash
                ? Yii::t('skeleton', 'PASSWORD_RESET_ACTIVE_ENTER_PLEASE')
                : Yii::t('skeleton', 'PASSWORD_RESET_ACTIVE_ENTER_BELOW'));
    }

    /**
     * {@see PasswordResetForm} has no `email` of its own, so the field is bound to the user its code resolved. It is
     * shown rather than edited, which is what `disabled` says — and what exempts it from the fieldset's safe check.
     */
    protected function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model->user)
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
