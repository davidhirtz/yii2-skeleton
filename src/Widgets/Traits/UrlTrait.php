<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait UrlTrait
{
    protected array|string|null $url = null;

    public function url(array|string|null $url): static
    {
        $this->url = $url;
        return $this;
    }
}
