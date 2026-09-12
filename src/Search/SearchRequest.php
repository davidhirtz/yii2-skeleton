<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final readonly class SearchRequest
{
    /**
     * @param list<string> $languages every indexed language when empty
     * @param list<class-string> $models every indexed class when empty
     * @param int|null $status the minimum status, `null` for any; the admin never sets it
     * @param int|null $tenantId a frontend filter only, see the plan in `docs/plans/fulltext-search.md`
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
