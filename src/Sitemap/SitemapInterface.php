<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

interface SitemapInterface
{
    /**
     * Returns the URLs of page `$offset`, or every URL when `$offset` is `null`.
     *
     * @return list<array<string, mixed>|string>
     */
    public function generateUrls(?int $offset = null): array;

    /**
     * The number of pages {@see generateUrls()} can be called with. It must never report fewer pages than there are
     * URLs, or the sitemap index silently drops the tail.
     */
    public function getPageCount(): int;

    public function getLastModified(int $offset = 0): ?string;
}
