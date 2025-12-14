<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

interface SitemapInterface
{
    public function generateSitemapUrls(int $offset = 0): array;

    public function getSitemapUrlCount(): int;
}
