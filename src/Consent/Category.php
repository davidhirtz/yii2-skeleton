<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Consent;

use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use yii\base\Configurable;

class Category implements Configurable
{
    use ContainerConfigurationTrait;

    protected string $id = '';
    protected string $title = '';
    protected string $description = '';
    protected bool $required = false;

    /**
     * @var list<Service>
     */
    protected array $services = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->configureProperties($config);
        }
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function services(Service ...$services): static
    {
        $this->services = array_values($services);
        return $this;
    }

    public function addService(Service ...$services): static
    {
        $this->services = [...$this->services, ...array_values($services)];
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return list<Service>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return list<string>
     */
    public function getFirstPartyCookieNames(): array
    {
        $names = [];

        foreach ($this->services as $service) {
            foreach ($service->getCookies() as $cookie) {
                if (!$cookie->isThirdParty()) {
                    $names[] = $cookie->getName();
                }
            }
        }

        return $names;
    }
}
