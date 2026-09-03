<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Icon;
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
    protected string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'password-recover-form';

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
        ];

        $this->submitButtonText = Lang::t('skeleton', 'COMMON_SEND_EMAIL');

        parent::configure();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->content(Lang::t('skeleton', 'PASSWORD_RECOVER_ACTIVE_ENTER_YOUR_EMAIL_ADDRESS_AND_WE'));
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
