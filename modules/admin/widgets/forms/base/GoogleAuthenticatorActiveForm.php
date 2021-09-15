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
     * @var int
     */
    public $qrCodeSize = 150;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->buttons) {
            $this->buttons = [$this->button($this->model->user->google_2fa_secret ? Yii::t('skeleton', 'Disable') : Yii::t('skeleton', 'Enable'))];
        }

        if (!$this->action) {
            $this->action = $this->model->user->google_2fa_secret ? ['account/disable-google-authenticator'] : ['account/enable-google-authenticator'];
        }

        parent::init();
    }

    /**
     * Renders the QR Code image
     */
    public function renderHeader()
    {
        if ($this->model->user->google_2fa_secret) {
            echo $this->textRow(Yii::t('skeleton', 'Two-factor authentication is enabled. Please enter the 6-digit code provided by your Google Authenticator app below to disable it.'));
        } else {
            echo $this->textRow(Yii::t('skeleton', 'To activate two-factor authentication please scan the QR code below with your Google Authenticator app and enter the 6-digit code. After completing this setup you will need to use the Google Authenticator for every login for extra security.'));
            echo $this->row($this->offset($this->getQrCodeImage()));
        }
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

        return Html::img($this->model->getQrImageUrl($this->qrCodeSize), $options);
    }
}