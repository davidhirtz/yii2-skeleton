<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use davidhirtz\yii2\datetime\DateTime;

/**
 * Built by {@see \Hirtz\Skeleton\Models\Interfaces\SearchableInterface::getSearchResult()}, which returns `null`
 * when the current user may not see the record.
 */
final readonly class SearchResult
{
    /**
     * @param array<array-key, mixed>|false $route the admin route
     * @param string|null $description HTML-free text the list snippets and highlights
     * @param string|null $url the frontend URL, for a project's own result page
     * @param list<string> $subtitles how the record's page header names it below `$title`, "Section 2/5"
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
