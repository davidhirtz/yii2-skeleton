<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagLinkTrait
{
    public function current(array $params = [], bool $scheme = false): self
    {
        return $this->addAttributes([
            'href' => Url::current($params, $scheme),
            'type' => null,
        ]);
    }

    public function href(string|array|null $route): self
    {
        return $this->addAttributes([
            'href' => $route !== null ? Url::to($route) : null,
            'type' => null,
        ]);
    }

    public function target(string $target): self
    {
        return $this->attribute('target', $target);
    }
}
