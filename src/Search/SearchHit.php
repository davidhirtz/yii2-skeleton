<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final readonly class SearchHit
{
    /**
     * @param class-string $modelClass
     */
    public function __construct(
        public string $modelClass,
        public int $modelId,
        public float $score,
    ) {
    }
}
