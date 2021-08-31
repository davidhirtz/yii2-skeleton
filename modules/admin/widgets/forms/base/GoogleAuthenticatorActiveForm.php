<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;

/**
 * Class GoogleAuthenticatorActiveForm
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms\base
 *
 * @property GoogleAuthenticatorForm $model
 */
class GoogleAuthenticatorActiveForm extends ActiveForm
{
    /**
     * @var string[]
     */
    public $action = ['google-authenticator/create'];

    /**
     * @var int
     */
    public $qrCodeSize = 150;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->buttons) {
            $this->buttons = [$this->button(Yii::t('skeleton', 'Activate'))];
        }
        parent::init();
    }

    /**
     * Renders the QR Code image
     */
    public function renderHeader()
    {
        echo $this->textRow(Yii::t('skeleton', 'To activate two-factor authentication please scan the QR code below with the Google Authenticator application and enter the 6-digit code. After completing this setup you will need to use the Google Authenticator for every login for extra security.'));
        echo $this->row($this->offset($this->getQrCodeImage()));
    }

    /**
     * @param array $options
     */
    public function renderFields($options = [])
    {
        echo $this->field($this->model, 'code', $options);
    }

    /**
     * @param array $options
     * @return string
     */
    public function getQrCodeImage($options = [])
    {
        if ($this->qrCodeSize) {
            Html::addCssStyle($options, "width:{$this->qrCodeSize}px;height:{$this->qrCodeSize}px;");
        }

        return Html::img($this->getQrImageUrl(), $options);
    }

    /**
     * @return string
     */
    protected function getQrImageUrl()
    {
        $otpAuthUri = $this->getOTPAuthUri();
        return "https://api.qrserver.com/v1/create-qr-code/?size={$this->qrCodeSize}x{$this->qrCodeSize}&data={$otpAuthUri}&ecc=M";
    }

    /**
     * @return string
     */
    protected function getOTPAuthUri()
    {
        $issuer = str_replace(':', '-', $this->getGoogleAuthenticatorIssuer());
        return rawurlencode("otpauth://totp/{$issuer}:{$this->model->user->email}?secret={$this->model->getSecret()}&issuer={$issuer}");
    }

    /**
     * @return string
     */
    protected function getGoogleAuthenticatorIssuer(): string
    {
        return Yii::$app->name;
    }
}