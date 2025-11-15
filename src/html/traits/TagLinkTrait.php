<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagLinkTrait
{
    public function current(array $params = [], bool $scheme = false): static
    {
        return $this->addAttributes([
            'href' => Url::current($params, $scheme),
            'type' => null,
        ]);
    }

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

    public function target(string $target): static
    {
        return $this->attribute('target', $target);
    }
}
