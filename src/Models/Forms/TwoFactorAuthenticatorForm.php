<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Override;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\Model;

class TwoFactorAuthenticatorForm extends Model
{
    use ModelTrait;

    public ?string $code = null;
    private ?string $secret = null;

    public function __construct(public readonly User $user, array $config = [])
    {
        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['code'],
                'required',
            ],
            [
                ['code'],
                TwoFactorAuthenticationValidator::class,
                'secret' => $this->getSecret(),
            ],
        ];
    }

    public function save(): bool
    {
        if ($this->validate()) {
            $this->user->google_2fa_secret = $this->getSecret();
            return $this->user->update() === 1;
        }

        return false;
    }

    public function delete(): false|int
    {
        if ($this->validate()) {
            Yii::$app->getSession()->set('google_2fa_secret', null);
            $this->user->google_2fa_secret = null;
            return $this->user->update();
        }

        return false;
    }

    public function getSecret(): string
    {
        $this->secret ??= $this->user->google_2fa_secret ?: Yii::$app->getSession()->get('google_2fa_secret');

        if (!$this->secret) {
            $this->generateSecret();
        }

        return $this->secret;
    }

    public function getQrImageUrl(int|string $size): string
    {
        $issuer = str_replace(':', '-', $this->getTwoFactorAuthenticationIssuer());
        $label = "$issuer:{$this->user->email}";

        $provider = new QRServerProvider();
        $auth = new TwoFactorAuth($provider, $issuer);

        return $provider->getUrl($auth->getQRText($label, $this->getSecret()), $size);
    }

    /**
     * Generates a new secret key and saves it to session.
     */
    protected function generateSecret(): void
    {
        $this->secret = (new TwoFactorAuth(new QRServerProvider()))->createSecret();

        Yii::$app->getSession()->set('google_2fa_secret', $this->secret);
        Yii::debug('New authenticator secret generated');
    }

    protected function getTwoFactorAuthenticationIssuer(): string
    {
        return Yii::$app->params['twoFactorAuthenticationIssuer'] ?? Yii::$app->name;
    }

    #[Override]
    public function formName(): string
    {
        return 'GoogleAuthenticator';
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'code' => Yii::t('skeleton', 'Code'),
        ];
    }
}
