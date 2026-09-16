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
     * Swaps one element of the page rather than the body's `#wrap`: a swap of the whole page empties the document
     * for an instant, which clamps the scroll position to the top. The `hx-swap` beats the body's `show:top`, and
     * naming an element in `$selectOob` replaces the body's own `hx-select-oob`, so the flashes are named again.
     *
     * @param array<int|string, mixed>|string $url
     * @param string $target the element to swap, under an id both the page and the response resolve
     * @param string|null $selectOob what the response also refreshes, such as a counter outside the target
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
        $this->attributes['hx-swap'] = 'show:window:top';
    }
}
