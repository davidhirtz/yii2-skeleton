<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Db\DateTime;

final readonly class SearchResult
{
    /**
     * @param array<array-key, mixed>|false $route
     * @param list<string> $subtitles
     */
    public function __construct(
        public string $title,
        public array|false $route = false,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $icon = null,
        public ?DateTime $updated = null,
        public array $subtitles = [],
        public ?string $type = null,
        public ?string $badge = null,
        public ?int $tenantId = null,
    ) {
    }

    public function withBadge(string $badge): self
    {
        return $this->with(badge: $badge);
    }

    /**
     * @param array<array-key, mixed>|false $route
     */
    public function withRoute(array|false $route): self
    {
        return $this->with(route: $route);
    }

    /**
     * @param array<array-key, mixed>|false|null $route
     */
    private function with(array|false|null $route = null, ?string $badge = null): self
    {
        return new self(
            $this->title,
            $route ?? $this->route,
            $this->description,
            $this->url,
            $this->icon,
            $this->updated,
            $this->subtitles,
            $this->type,
            $badge ?? $this->badge,
            $this->tenantId,
        );
    }
}
