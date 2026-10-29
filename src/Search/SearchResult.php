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
    ) {
    }
}
