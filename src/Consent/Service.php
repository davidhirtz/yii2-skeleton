<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use yii\base\Configurable;

class Service implements Configurable
{
    use ContainerConfigurationTrait;

    protected string $name = '';
    protected string $provider = '';
    protected string $purpose = '';
    protected ?string $privacyUrl = null;

    /**
     * @var list<Cookie>
     */
    protected array $cookies = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->configureProperties($config);
        }
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function provider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function purpose(string $purpose): static
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function privacyUrl(?string $privacyUrl): static
    {
        $this->privacyUrl = $privacyUrl;
        return $this;
    }

    public function cookies(Cookie ...$cookies): static
    {
        $this->cookies = array_values($cookies);
        return $this;
    }

    public function addCookie(Cookie ...$cookies): static
    {
        $this->cookies = [...$this->cookies, ...array_values($cookies)];
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getPrivacyUrl(): ?string
    {
        return $this->privacyUrl;
    }

    /**
     * @return list<Cookie>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }
}
