<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Search\SearchDocument;
use Hirtz\Skeleton\Search\SearchResult;

/**
 * Implemented via {@see SearchableTrait}, which leaves only {@see static::getSearchAttributes()} to the model.
 */
interface SearchableInterface
{
    /**
     * @return list<string> the attribute names whose values are indexed, translated and custom attributes included
     */
    public function getSearchAttributes(): array;

    public function getSearchTitle(?string $language = null): string;

    public function getSearchWeight(): float;

    public function getSearchTenantId(): ?int;

    public function getSearchStatus(): int;

    public function isSearchable(): bool;

    /**
     * @return SearchResult|null `null` hides the hit from the current user
     */
    public function getSearchResult(): ?SearchResult;

    /**
     * @param class-string|null $modelClass defaults to the record's own class
     * @return list<SearchDocument> one document per configured language
     */
    public function getSearchDocuments(?string $modelClass = null): array;

    /**
     * The query the rebuild and the hit hydration load records with, so a model can eager load what its documents
     * or its result need.
     */
    public static function findSearchable(): ActiveQuery;
}
