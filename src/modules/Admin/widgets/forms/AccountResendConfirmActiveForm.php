<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\LoginActiveFormTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;
use Stringable;
use Yii;

/**
 * @property AccountResendConfirmForm $model
 */
class AccountResendConfirmActiveForm extends ActiveForm
{
    use LoginActiveFormTrait;

    public array $attributes = ['class' => 'form-plain'];
    public array $excludedErrorProperties = ['email'];
    public bool $hasStickyButtons = false;
    public string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'resend-form';

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
        ];

        $this->submitButtonText ??= Yii::t('skeleton', 'Send Email');

        parent::configure();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->text(Yii::t('skeleton', 'Enter your email address and we will send you another email to confirm your account.'));
    }
}
