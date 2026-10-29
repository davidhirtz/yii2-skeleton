<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons\Traits;

use Hirtz\Skeleton\Helpers\Url;

trait AjaxAttributesTrait
{
    /**
     * @param array<int|string, mixed>|string $url
     */
    public function delete(string|array $url, string $target): static
    {
        $this->attributes['hx-select'] = $target;
        $this->attributes['hx-swap'] = 'delete';
        $this->attributes['hx-target'] = $target;

        return $this->post($url);
    }

    /**
     * Swaps `$target` (an id both page and response resolve) rather than `#wrap`, whose swap clamps the scroll to the
     * top; a `$selectOob` replaces the body's `hx-select-oob`, so the flashes are named again.
     *
     * @param array<int|string, mixed>|string $url
     */
    public function replace(string|array $url, string $target, ?string $selectOob = null): static
    {
        $this->attributes['hx-select'] = $target;
        $this->attributes['hx-swap'] = 'outerHTML';
        $this->attributes['hx-target'] = $target;

        if ($selectOob) {
            $this->attributes['hx-select-oob'] = "#flashes:beforeend,$selectOob";
        }

        return $this->post($url);
    }

    /**
     * @param array<int|string, mixed>|string $url
     */
    public function get(string|array $url, bool $pushHistory = true): static
    {
        $this->attributes['hx-get'] = Url::to($url);

        if ($pushHistory) {
            $this->addPushHistoryAttributes();
        }

        return $this;
    }

    /**
     * @param array<int|string, mixed>|string $url
     */
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
        $this->attributes['hx-swap'] = 'outerHTML show:top';
    }
}
