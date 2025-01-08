<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagAjaxAttributeTrait
{
    public function delete(string|array $url, string $target): self
    {
        return $this->addAttributes([
            'hx-post' => Url::to($url),
            'hx-select-oob' => '#flashes',
            'hx-swap' => 'delete',
            'hx-target' => $target,
        ]);
    }

    public function get(string|array $url): self
    {
        return $this->attribute('hx-get', Url::to($url));
    }

    public function post(string|array $url): self
    {
        return $this->attribute('hx-post', Url::to($url));
    }
}
