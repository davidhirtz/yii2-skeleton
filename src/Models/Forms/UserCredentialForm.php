<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Credentials\CredentialProvider;
use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserCredential;
use Override;
use Yii;
use yii\base\Model;

/**
 * The fields are the chosen provider's, as custom attributes: changing the provider reloads the form.
 */
class UserCredentialForm extends Model implements CustomAttributeInterface
{
    use CustomAttributesTrait;
    use ModelTrait;

    public ?string $provider = null;

    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(public readonly User $user, array $config = [])
    {
        $this->provider = array_key_first(Credentials::getComponent()->getProviders());
        parent::__construct($config);
    }

    #[Override]
    public function attributes(): array
    {
        return ['provider', ...$this->getCustomAttributeNames()];
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['provider'],
                'required',
            ],
            [
                ['provider'],
                'in',
                'range' => array_keys(Credentials::getComponent()->getProviders()),
            ],
            ...$this->getCustomAttributeRules(),
            [
                ['provider'],
                $this->validateValues(...),
            ],
        ];
    }

    public function validateValues(): void
    {
        $provider = $this->getProvider();

        if ($provider && !$this->hasErrors() && !$provider->isValid($this->getValues())) {
            $names = $this->getCustomAttributeNames();
            $this->addError((string)end($names), Yii::t('skeleton', 'CREDENTIAL_SECRET_ERROR'));
        }
    }

    public function save(): ?UserCredential
    {
        if (!$this->validate()) {
            return null;
        }

        $credential = UserCredential::store($this->user->id, (string)$this->provider, $this->getValues());
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
    public function getCustomAttributes(): array
    {
        return $this->getProvider()?->getFields() ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function getValues(): array
    {
        $values = [];

        foreach ($this->getCustomAttributeNames() as $name) {
            $values[$name] = trim((string)($this->values[$name] ?? ''));
        }

        return $values;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'provider' => Yii::t('skeleton', 'CREDENTIAL_PROVIDER_LABEL'),
            ...$this->getCustomAttributeLabels(),
        ];
    }

    /**
     * Where to get a key goes under the last field, since a single provider is not offered as a choice.
     */
    #[Override]
    public function attributeHints(): array
    {
        $url = $this->getProvider()?->getUrl();
        $names = $this->getCustomAttributeNames();

        return [
            ...array_filter($this->getCustomAttributeHints(), fn (?string $hint): bool => $hint !== null),
            ...$url ? [(string)end($names) => Yii::t('skeleton', 'CREDENTIAL_SECRET_HINT') . " $url"] : [],
        ];
    }

    protected function getCustomAttributesKey(): mixed
    {
        return $this->provider;
    }

    public function hasAttribute(string $name): bool
    {
        return in_array($name, $this->getCustomAttributeNames(), true);
    }

    public function getAttribute(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    #[Override]
    public function __get($name)
    {
        return $this->hasAttribute((string)$name) ? $this->getAttribute((string)$name) : parent::__get($name);
    }

    #[Override]
    public function __set($name, $value): void
    {
        if ($this->hasAttribute((string)$name)) {
            $this->setAttribute((string)$name, $value);
            return;
        }

        parent::__set($name, $value);
    }

    #[Override]
    public function __isset($name): bool
    {
        return $this->hasAttribute((string)$name) ? $this->getAttribute((string)$name) !== null : parent::__isset($name);
    }
}
