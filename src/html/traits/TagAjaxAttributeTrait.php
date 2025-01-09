<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagAjaxAttributeTrait
{
    public function delete(string|array $url, string $target): static
    {
        $this->attributes['hx-post'] = Url::to($url);
        $this->attributes['hx-select-oob'] = '#flashes';
        $this->attributes['hx-swap'] = 'delete';
        $this->attributes['hx-target'] = $target;
        return $this;
    }

    public function get(string|array $url): static
    {
        $this->attributes['hx-get'] = Url::to($url);
        return $this;
    }

    public function post(string|array $url): static
    {
        $this->attributes['hx-post'] = Url::to($url);
        return $this;
    }
}
