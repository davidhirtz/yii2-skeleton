<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final readonly class SearchResultSet
{
    /**
     * @param list<SearchHit> $hits
     */
    public function __construct(public array $hits = [])
    {
    }
}
