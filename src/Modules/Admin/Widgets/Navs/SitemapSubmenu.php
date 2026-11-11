<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Override;
use Yii;
use yii\helpers\Inflector;

class SitemapSubmenu extends Submenu
{
    protected string $key = '';
    protected int $offset = 0;

    public function current(string $key, int $offset): static
    {
        $this->key = $key;
        $this->offset = $offset;

        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->backUrl ??= false;

        foreach (Sitemap::getComponent()->getSitemaps() as $key => $sitemap) {
            $pageCount = max(1, $sitemap->getPageCount());

            for ($offset = 0; $offset < $pageCount; $offset++) {
                $this->addItem(NavItem::make()
                    ->label($this->getLabel((string)$key, $offset, $pageCount))
                    ->url(['/admin/sitemap/index', 'key' => $key, 'offset' => $offset ?: null])
                    ->active((string)$key === $this->key && $offset === $this->offset));
            }
        }

        parent::configure();
    }

    protected function getLabel(string $key, int $offset, int $pageCount): string
    {
        $label = $key === Sitemap::URLS_KEY
            ? Yii::t('skeleton', 'SITEMAP_URLS_LABEL')
            : Inflector::camel2words($key);

        return $pageCount > 1 ? "$label " . ($offset + 1) : $label;
    }
}
