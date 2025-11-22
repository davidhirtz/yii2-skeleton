<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\html\Img;
use davidhirtz\yii2\skeleton\models\forms\TwoFactorAuthenticatorForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
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
        $this->submitButtonText ??= $enabled ? Yii::t('skeleton', 'Disable') : Yii::t('skeleton', 'Enable');
        $this->footer ??= false;

        parent::configure();
    }

    protected function getDisableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content(Yii::t('skeleton', 'Two-factor authentication is enabled. Please enter the 6-digit code provided by your Authenticator app below to disable it.')),
            $this->getInputField(),
        ];
    }

    protected function getEnableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content(
                    Yii::t('skeleton', 'To activate two-factor authentication please scan the QR code below with your Authenticator app and enter the 6-digit code. After completing this setup you will need to use the Authenticator for every login for extra security.')),
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
            ->alt(Yii::t('skeleton', 'Authenticator QR Code'))
            ->addStyle([
                'width' => "{$this->qrCodeSize}px",
                'height' => "{$this->qrCodeSize}px",
            ]);
    }
}
