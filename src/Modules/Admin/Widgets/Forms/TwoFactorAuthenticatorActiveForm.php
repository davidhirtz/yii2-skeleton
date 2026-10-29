<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

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
    protected int $qrCodeSize = 150;
    protected bool $hasStickyButtons = false;

    protected TwoFactorAuthenticatorForm $authenticator;

    public function qrCodeSize(int $qrCodeSize): static
    {
        $this->qrCodeSize = $qrCodeSize;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $enabled = $this->model->hasTwoFactorAuthentication();

        $this->authenticator = TwoFactorAuthenticatorForm::create([
            'user' => $this->model,
            'config' => [
                'scenario' => $enabled
                    ? TwoFactorAuthenticatorForm::SCENARIO_DEFAULT
                    : TwoFactorAuthenticatorForm::SCENARIO_ENABLE,
            ],
        ]);

        $this->action ??= $enabled ? ['account/disable-authenticator'] : ['account/enable-authenticator'];
        $this->submitButtonText ??= $enabled ? Yii::t('skeleton', 'TWO_FACTOR_DISABLE_BUTTON') : Yii::t('skeleton', 'TWO_FACTOR_ENABLE_BUTTON');
        $this->footer ??= false;

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return $this->model->hasTwoFactorAuthentication()
            ? $this->getDisableAuthenticatorRows()
            : $this->getEnableAuthenticatorRows();
    }

    /**
     * @return list<string|Stringable>
     */
    protected function getDisableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content(Yii::t('skeleton', 'TWO_FACTOR_RECOVERY_CODES_COUNT_TEXT', [
                    'count' => $this->model->getTwoFactorAuthenticationRecoveryCodeCount(),
                ])),
            $this->getInputField(),
        ];
    }

    /**
     * @return list<string|Stringable>
     */
    protected function getEnableAuthenticatorRows(): array
    {
        return [
            FormRow::make()
                ->content($this->getQrCodeImage()),
            $this->getInputField(),
            $this->getPasswordField(),
        ];
    }

    protected function getPasswordField(): string|Stringable
    {
        if (!$this->authenticator->isPasswordRequired()) {
            return '';
        }

        return InputField::make()
            ->model($this->authenticator)
            ->property('password')
            ->type('password');
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
            ->alt(Yii::t('skeleton', 'TWO_FACTOR_QR_CODE_LABEL'))
            ->addStyle([
                'width' => "{$this->qrCodeSize}px",
                'height' => "{$this->qrCodeSize}px",
            ]);
    }
}
