<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final readonly class SearchRequest
{
    /**
     * `$status` and `$tenantId` are frontend filters the admin never sets.
     *
     * @param list<string> $languages
     * @param list<class-string> $models
     */
    public function __construct(
        public string $query,
        public array $languages = [],
        public array $models = [],
        public ?int $tenantId = null,
        public ?int $status = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }

    public function withLimit(int $limit, int $offset = 0): self
    {
        return new self(
            $this->query,
            $this->languages,
            $this->models,
            $this->tenantId,
            $this->status,
            $limit,
            $offset,
        );
    }
}
