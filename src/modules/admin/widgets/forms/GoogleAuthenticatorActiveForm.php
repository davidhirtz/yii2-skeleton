<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;
use yii\widgets\ActiveField;

/**
 * @property GoogleAuthenticatorForm $model
 */
class GoogleAuthenticatorActiveForm extends ActiveForm
{
    public int $qrCodeSize = 150;

    /**
     * @see AccountController::actionEnableGoogleAuthenticator()
     * @see AccountController::actionDisableGoogleAuthenticator()
     */
    public function init(): void
    {
        $this->buttons ??= [
            $this->button($this->model->user->google_2fa_secret
                ? Yii::t('skeleton', 'Disable')
                : Yii::t('skeleton', 'Enable')),
        ];

        if (!$this->action) {
            $this->action = $this->model->user->google_2fa_secret
                ? ['account/disable-google-authenticator']
                : ['account/enable-google-authenticator'];
        }

        parent::init();
    }

    /**
     * Renders the QR Code image
     */
    public function renderHeader(): void
    {
        if ($this->model->user->google_2fa_secret) {
            echo $this->textRow(Yii::t('skeleton', 'Two-factor authentication is enabled. Please enter the 6-digit code provided by your Google Authenticator app below to disable it.'));
        } else {
            echo $this->textRow(Yii::t('skeleton', 'To activate two-factor authentication please scan the QR code below with your Google Authenticator app and enter the 6-digit code. After completing this setup you will need to use the Google Authenticator for every login for extra security.'));
            echo $this->row($this->offset($this->getQrCodeImage()));
        }
    }

    public function renderFields(): void
    {
        echo $this->codeField();
    }

    public function codeField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'code', $options);
    }

    public function getQrCodeImage(array $options = []): string
    {
        if ($this->qrCodeSize) {
            Html::addCssStyle($options, "width:{$this->qrCodeSize}px;height:{$this->qrCodeSize}px;");
        }

        return Html::img($this->model->getQrImageUrl($this->qrCodeSize), $options);
    }
}
