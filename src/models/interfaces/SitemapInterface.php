<?php

namespace davidhirtz\yii2\skeleton\models\interfaces;

interface SitemapInterface
{
    public function generateSitemapUrls(int $offset = 0): array;
    public function getSitemapUrlCount(): int;
}
