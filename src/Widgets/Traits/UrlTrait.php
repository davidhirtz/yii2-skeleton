<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait UrlTrait
{
    /**
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $url = null;

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function url(array|string|null $url): static
    {
        $this->url = $url;
        return $this;
    }
}
