<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Credentials\CredentialProvider;
use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserCredential;
use Override;
use Yii;
use yii\base\Model;

class UserCredentialForm extends Model
{
    public ?string $provider = null;
    public ?string $secret = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(public readonly User $user, array $config = [])
    {
        $this->provider = array_key_first(Credentials::getComponent()->getProviders());
        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['provider', 'secret'],
                'required',
            ],
            [
                ['provider'],
                'in',
                'range' => array_keys(Credentials::getComponent()->getProviders()),
            ],
            [
                ['secret'],
                'trim',
            ],
            [
                ['secret'],
                $this->validateSecret(...),
            ],
        ];
    }

    public function validateSecret(): void
    {
        $provider = $this->getProvider();

        if ($provider && !$this->hasErrors() && !$provider->isValid((string)$this->secret)) {
            $this->addError('secret', Yii::t('skeleton', 'CREDENTIAL_ERROR_INVALID'));
        }
    }

    public function save(): ?UserCredential
    {
        if (!$this->validate()) {
            return null;
        }

        $credential = UserCredential::store($this->user->id, (string)$this->provider, (string)$this->secret);
        return $credential->hasErrors() ? null : $credential;
    }

    public function getProvider(): ?CredentialProvider
    {
        return Credentials::getComponent()->getProvider((string)$this->provider);
    }

    /**
     * @return array<string, string>
     */
    public function getProviderItems(): array
    {
        return array_map(fn (CredentialProvider $provider): string => $provider->getLabel(), Credentials::getComponent()->getProviders());
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'provider' => Yii::t('skeleton', 'CREDENTIAL_PROVIDER_LABEL'),
            'secret' => Yii::t('skeleton', 'CREDENTIAL_SECRET_LABEL'),
        ];
    }

    #[Override]
    public function attributeHints(): array
    {
        $links = array_filter(array_map(
            fn (CredentialProvider $provider): ?string => $provider->getUrl() ? "{$provider->getLabel()}: {$provider->getUrl()}" : null,
            Credentials::getComponent()->getProviders(),
        ));

        // On the key, as a single provider is not offered as a choice at all
        return [
            'secret' => trim(Yii::t('skeleton', 'CREDENTIAL_HINT') . ($links ? ' ' . Yii::t('skeleton', 'CREDENTIAL_GET_KEY') . ' ' . implode(', ', $links) : '')),
        ];
    }
}
