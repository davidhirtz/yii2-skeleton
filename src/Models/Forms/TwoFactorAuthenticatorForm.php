<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Hirtz\Skeleton\Web\Application;
use Override;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\Model;

class TwoFactorAuthenticatorForm extends Model
{
    use ModelTrait;

    final public const string SESSION_SECRET_NAME = 'two_factor_secret';

    public ?string $code = null;

    /**
     * @var list<string> the recovery codes in the clear, populated by a successful {@see static::save()} and the
     * only chance anyone has to write them down.
     */
    public array $recoveryCodes = [];

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
        if (!$this->validate()) {
            return false;
        }

        $this->user->setTwoFactorAuthenticationSecret($this->getSecret());
        $this->recoveryCodes = $this->user->generateTwoFactorAuthenticationRecoveryCodes();

        Application::current()->getSession()->set(static::SESSION_SECRET_NAME, null);

        return $this->user->update() === 1;
    }

    public function delete(): false|int
    {
        if (!$this->validateDeleteCode()) {
            return false;
        }

        Application::current()->getSession()->set(static::SESSION_SECRET_NAME, null);
        $this->user->setTwoFactorAuthenticationSecret(null);

        return $this->user->update();
    }

    /**
     * A recovery code turns the second factor off as well as a code from the authenticator does. Without that, a
     * user who lost the device could log in with a recovery code and still not get out of it on their own.
     */
    private function validateDeleteCode(): bool
    {
        $isRecoveryCode = strlen((string)$this->code) === User::RECOVERY_CODE_LENGTH;

        return ($isRecoveryCode && $this->user->validateTwoFactorAuthenticationRecoveryCode($this->code))
            || $this->validate();
    }

    public function getSecret(): string
    {
        $this->secret ??= $this->user->getTwoFactorAuthenticationSecret() ?: Application::current()->getSession()->get(static::SESSION_SECRET_NAME);

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

        Application::current()->getSession()->set(static::SESSION_SECRET_NAME, $this->secret);
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
            'code' => Yii::t('skeleton', 'TWO_FACTOR_AUTHENTICATOR_CODE_LABEL'),
        ];
    }
}
