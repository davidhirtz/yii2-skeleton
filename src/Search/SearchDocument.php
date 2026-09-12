<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;

final readonly class SearchDocument
{
    /**
     * @param class-string $modelClass
     */
    public function __construct(
        public string $modelClass,
        public int $modelId,
        public string $language,
        public string $title,
        public string $content = '',
        public ?int $tenantId = null,
        public int $status = StatusAttributeInterface::STATUS_ENABLED,
        public float $weight = 1.0,
    ) {
    }
}
