<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

use yii\base\BaseObject;

abstract class AbstractSitemap extends BaseObject implements SitemapInterface
{
    /**
     * @var Sitemap|null the component this sitemap was created by, assigned by {@see Sitemap::getSitemaps()}.
     */
    public ?Sitemap $sitemap = null;

    /**
     * @var int|null the maximum number of URLs per page, defaults to {@see Sitemap::$maxUrlCount}.
     */
    public ?int $maxUrlCount = null;

    public function getLastModified(int $offset = 0): ?string
    {
        return null;
    }

    public function getMaxUrlCount(): int
    {
        return $this->maxUrlCount ??= $this->getSitemapComponent()->maxUrlCount;
    }

    protected function getSitemapComponent(): Sitemap
    {
        return $this->sitemap ??= Sitemap::getComponent();
    }
}
