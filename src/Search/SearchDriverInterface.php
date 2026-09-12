<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

/**
 * Deliberately thin: documents in, ids and scores out, so a real search engine can replace {@see MysqlDriver}
 * without touching the models or the hydration.
 */
interface SearchDriverInterface
{
    public function index(SearchDocument ...$documents): void;

    /**
     * @param class-string $modelClass
     */
    public function delete(string $modelClass, int $modelId): void;

    public function search(SearchRequest $request): SearchResultSet;

    public function count(SearchRequest $request): int;

    /**
     * @param class-string|null $modelClass
     */
    public function clear(?string $modelClass = null): void;
}
