<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\Model;

/**
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class GoogleAuthenticatorForm extends Model
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    private $_secret;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['code'],
                'required',
            ],
            [
                ['code'],
                GoogleAuthenticatorValidator::class,
                'secret' => $this->getSecret(),
            ],
        ];
    }

    /**
     * Updates user with the generated secret.
     * @return bool
     */
    public function save()
    {
        if ($this->validate()) {
            $this->user->google_2fa_secret = $this->getSecret();
            return $this->user->update();
        }

        return false;
    }

    /**
     * Removes secret from user.
     */
    public function delete()
    {
        if ($this->validate()) {
            $this->user->google_2fa_secret = null;
            return $this->user->update();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        if ($this->_secret === null) {
            if (!($this->_secret = $this->user->google_2fa_secret ?: Yii::$app->getSession()->get('google_2fa_secret'))) {
                $this->generateSecret();
            }
        }

        return $this->_secret;
    }

    /**
     * @param int|string $size
     * @return string
     */
    public function getQrImageUrl($size)
    {
        $issuer = str_replace(':', '-', $this->getGoogleAuthenticatorIssuer());
        $label = "{$issuer}:{$this->user->email}";
        $auth = new TwoFactorAuth($issuer);

        return $auth->getQrCodeProvider()->getUrl($auth->getQRText($label, $this->getSecret()), $size);
    }

    /**
     * Generates a new secret key and saves it to session.
     */
    protected function generateSecret(): void
    {
        Yii::$app->getSession()->set('google_2fa_secret', $this->_secret = (new TwoFactorAuth())->createSecret());
        Yii::debug('New Google Authenticator secret generated');
    }

    /**
     * @return string
     */
    protected function getGoogleAuthenticatorIssuer(): string
    {
        return Yii::$app->params['googleAuthenticatorIssuer'] ?? Yii::$app->name;
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'GoogleAuthenticator';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('skeleton', 'Code'),
        ];
    }
}