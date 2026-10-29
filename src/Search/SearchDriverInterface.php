<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

/**
 * Deliberately thin, so a real search engine can replace {@see MysqlDriver} without touching models or hydration.
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
