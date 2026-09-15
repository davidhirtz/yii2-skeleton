<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

use Hirtz\Skeleton\Helpers\Url;

trait TagLinkTrait
{
    /**
     * @param array<string, mixed> $params
     */
    public function current(array $params = [], bool $scheme = false): static
    {
        return $this->addAttributes([
            'href' => Url::current($params, $scheme),
            'type' => null,
        ]);
    }

    public function ariaLabel(string $label): static
    {
        return $this->attribute('aria-label', $label);
    }

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function href(string|array|null $url): static
    {
        return $this->addAttributes([
            'href' => $url !== null ? Url::to($url) : null,
            'type' => null,
        ]);
    }

    public function mailto(string $email): static
    {
        return $this->addAttributes([
            'href' => "mailto:$email",
            'type' => null,
        ]);
    }

    public function target(?string $target): static
    {
        return $this->attribute('target', $target);
    }
}
