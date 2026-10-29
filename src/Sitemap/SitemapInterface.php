<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

interface SitemapInterface
{
    /**
     * `null` answers every URL.
     *
     * @return list<array<string, mixed>|string>
     */
    public function generateUrls(?int $offset = null): array;

    /**
     * Must never report fewer pages than there are URLs, or the sitemap index silently drops the tail.
     */
    public function getPageCount(): int;

    public function getLastModified(int $offset = 0): ?string;
}
