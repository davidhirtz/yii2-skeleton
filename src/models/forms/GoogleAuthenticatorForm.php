<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\Model;

class GoogleAuthenticatorForm extends Model
{
    use ModelTrait;

    public ?string $code = null;
    private ?string $_secret = null;

    public function __construct(public User $user, array $config = [])
    {
        parent::__construct($config);
    }

    public function rules(): array
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

    public function save(): bool
    {
        if ($this->validate()) {
            $this->user->google_2fa_secret = $this->getSecret();
            return $this->user->update();
        }

        return false;
    }

    public function delete(): false|int
    {
        if ($this->validate()) {
            $this->user->google_2fa_secret = null;
            return $this->user->update();
        }

        return false;
    }

    public function getSecret(): string
    {
        $this->_secret ??= $this->user->google_2fa_secret ?: Yii::$app->getSession()->get('google_2fa_secret');

        if (!$this->_secret) {
            $this->generateSecret();
        }

        return $this->_secret;
    }

    public function getQrImageUrl(int|string $size): string
    {
        $issuer = str_replace(':', '-', $this->getGoogleAuthenticatorIssuer());
        $label = "$issuer:{$this->user->email}";
        $auth = new TwoFactorAuth($issuer);

        /** @var QRServerProvider $qrProvider */
        $qrProvider = $auth->getQrCodeProvider();

        return $qrProvider->getUrl($auth->getQRText($label, $this->getSecret()), $size);
    }

    /**
     * Generates a new secret key and saves it to session.
     */
    protected function generateSecret(): void
    {
        Yii::$app->getSession()->set('google_2fa_secret', $this->_secret = (new TwoFactorAuth())->createSecret());
        Yii::debug('New Google Authenticator secret generated');
    }

    protected function getGoogleAuthenticatorIssuer(): string
    {
        return Yii::$app->params['googleAuthenticatorIssuer'] ?? Yii::$app->name;
    }

    public function formName(): string
    {
        return 'GoogleAuthenticator';
    }

    public function attributeLabels(): array
    {
        return [
            'code' => Yii::t('skeleton', 'Code'),
        ];
    }
}
