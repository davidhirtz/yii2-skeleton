<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait TagAjaxAttributeTrait
{
    public function delete(string|array $url, string $target): static
    {
        $this->attributes['hx-select'] = $target;
        $this->attributes['hx-swap'] = 'delete';
        $this->attributes['hx-target'] = $target;

        return $this->post($url);
    }

    public function replace(string|array $url, string $target): static
    {
        $this->attributes['hx-select'] = $target;
        $this->attributes['hx-target'] = $target;

        return $this->post($url);
    }

    public function get(string|array $url, bool $pushHistory = true): static
    {
        $this->attributes['hx-get'] = Url::to($url);

        if ($pushHistory) {
            $this->addPushHistoryAttributes();
        }

        return $this;
    }

    public function post(string|array $url, bool $pushHistory = false): static
    {
        $this->attributes['hx-post'] = Url::to($url);

        if ($pushHistory) {
            $this->addPushHistoryAttributes();
        }

        return $this;
    }

    private function addPushHistoryAttributes(): void
    {
        $this->attributes['hx-push-url'] = 'true';
        $this->attributes['hx-swap'] = 'show:window:top';
    }
}
