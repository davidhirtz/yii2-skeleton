<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Credentials;

use Closure;

/**
 * A third-party service a user may store a key of their own for, declared by the bundle that calls it.
 */
class CredentialProvider
{
    protected ?string $label = null;
    protected ?string $url = null;
    protected ?Closure $verify = null;

    final public function __construct(public readonly string $name)
    {
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Where a user gets a key, shown beside the form.
     */
    public function url(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param Closure(string): bool $verify a cheap call to the service, so a mistyped key is refused when it is saved
     */
    public function verify(Closure $verify): static
    {
        $this->verify = $verify;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function isValid(string $secret): bool
    {
        return $this->verify === null || ($this->verify)($secret);
    }
}
