<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagAjaxAttributeTrait
{
    public function delete(string|array $url, string $target): static
    {
        $this->attributes['hx-swap'] = 'delete';
        $this->post($url, $target);

        return $this;
    }

    public function get(string|array $url, bool $pushHistory = true): static
    {
        $this->attributes['hx-get'] = Url::to($url);
        $this->attributes['hx-push-url'] = $pushHistory ? 'true' : null;

        return $this;
    }

    public function post(string|array $url, string $target = null): static
    {
        $this->attributes['hx-post'] = Url::to($url);
        $this->attributes['hx-select'] = $target;
        $this->attributes['hx-target'] = $target;

        return $this;
    }
}
