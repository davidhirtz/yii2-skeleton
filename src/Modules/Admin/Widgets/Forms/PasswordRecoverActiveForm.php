<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use Stringable;
use Yii;

/**
 * @property PasswordRecoverForm $model
 */
class PasswordRecoverActiveForm extends ActiveForm
{
    public array $attributes = ['class' => 'form-plain'];
    public array $excludedErrorProperties = ['email'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'password-recover-form';

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
        ];

        $this->submitButtonText = Yii::t('skeleton', 'Send Email');

        parent::configure();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->content(Yii::t('skeleton', 'Enter your email address and we will send you instructions how to reset your password.'));
    }

    protected function getEmailField(): ?Stringable
    {
        return InputField::make()
            ->model($this->model)
            ->property('email')
            ->autocomplete('email')
            ->autofocus(!$this->model->hasErrors())
            ->prepend(Icon::make()->name('envelope'))
            ->placeholder()
            ->type('email');
    }
}
