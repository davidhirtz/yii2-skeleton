<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Search\SearchDocument;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Db\ActiveRecord;

interface SearchableInterface extends AdminModelInterface
{
    /**
     * Any readable property: translated and custom attributes and plain getters are allowed.
     *
     * @return list<string>
     */
    public function getSearchAttributes(): array;

    public function getSearchTitle(?string $language = null): string;

    public function getSearchWeight(): float;

    public function getSearchTenantId(): ?int;

    public function getSearchStatus(): int;

    public function isSearchable(): bool;

    /**
     * Whether the current user sees the class's hits at all, so the search can count only those.
     */
    public static function isSearchResultClassVisible(): bool;

    /**
     * `null` hides the hit from the current user.
     */
    public function getSearchResult(): ?SearchResult;

    /**
     * One document per configured language.
     *
     * @param class-string|null $modelClass
     * @return list<SearchDocument>
     */
    public function getSearchDocuments(?string $modelClass = null): array;

    /**
     * The rebuild and the hit hydration load records with it, so a model can eager load what they need.
     *
     * @return ActiveQuery<covariant ActiveRecord>
     */
    public static function findSearchable(): ActiveQuery;
}
