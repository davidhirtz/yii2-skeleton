<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\forms;

use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\Icon;
use Hirtz\Skeleton\models\forms\PasswordRecoverForm;
use Hirtz\Skeleton\widgets\forms\ActiveForm;
use Hirtz\Skeleton\widgets\forms\fields\InputField;
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
