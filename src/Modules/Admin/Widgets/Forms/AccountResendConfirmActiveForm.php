<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\I18n\Lang;
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
    protected string $layout = "{errors}{rows}{buttons}";

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'resend-form';

        $this->rows ??= [
            $this->getHelpText(),
            $this->getEmailField(),
        ];

        $this->submitButtonText ??= Lang::t('skeleton', 'COMMON_SEND_EMAIL');

        parent::configure();
    }

    protected function getHelpText(): ?Stringable
    {
        return Div::make()
            ->text(Lang::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_ACTIVE'));
    }
}
