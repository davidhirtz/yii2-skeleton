<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\Img;
use Hirtz\Skeleton\Models\Forms\TwoFactorAuthenticatorForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Override;
use Stringable;
use Yii;

/**
 * @see AccountController::actionDisableAuthenticator
 * @see AccountController::actionEnableAuthenticator
 *
 * @property User $model
 */
class TwoFactorAuthenticatorActiveForm extends ActiveForm
{
    public int $qrCodeSize = 150;
    public bool $hasStickyButtons = false;

    protected TwoFactorAuthenticatorForm $authenticator;

    #[Override]
    protected function configure(): void
    {
        $this->authenticator = TwoFactorAuthenticatorForm::create([
            'user' => $this->model,
        ]);

        $enabled = $this->model->google_2fa_secret;

        $this->action ??= $enabled ? ['account/disable-authenticator'] : ['account/enable-authenticator'];
        $this->rows ??= $enabled ? $this->getDisableAuthenticatorRows() : $this->getEnableAuthenticatorRows();
        $this->submitButtonText ??= $enabled ? Lang::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_ACTIVE_DISABLE') : Lang::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_ACTIVE_ENABLE');
        $this->footer ??= false;

        parent::configure();
    }

    protected function getDisableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content(Lang::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_ACTIVE_TWO_FACTOR_AUTHENTICATION_IS_ENABLED_PLEASE')),
            $this->getInputField(),
        ];
    }

    protected function getEnableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content(
                    Lang::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_ACTIVE_TO_ACTIVATE_TWO_FACTOR_AUTHENTICATION_PLEASE')
                ),
            FormRow::make()
                ->content($this->getQrCodeImage()),
            $this->getInputField(),
        ];
    }

    protected function getInputField(): Stringable
    {
        return InputField::make()
            ->model($this->authenticator)
            ->property('code');
    }

    protected function getQrCodeImage(): Stringable
    {
        return Img::make()
            ->src($this->authenticator->getQrImageUrl($this->qrCodeSize))
            ->alt(Lang::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_ACTIVE_AUTHENTICATOR_QR_CODE'))
            ->addStyle([
                'width' => "{$this->qrCodeSize}px",
                'height' => "{$this->qrCodeSize}px",
            ]);
    }
}
