<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\SitemapUrlArrayDataProvider;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class SitemapHeader extends Header
{
    /**
     * @use ProviderTrait<SitemapUrlArrayDataProvider>
     */
    use ProviderTrait;

    protected string $key = '';

    public function key(string $key): static
    {
        $this->key = $key;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'SITEMAP_TITLE');
        $this->url ??= ['/admin/sitemap/index'];

        if ($this->provider) {
            $this->subtitle ??= Yii::t('skeleton', 'SITEMAP_URL_COUNT_TEXT', [
                'count' => $this->provider->getTotalCount(),
            ]);
        }

        $this->addContent($this->getSearchConsoleButton(), $this->getOpenButton());
        $this->addSystemBreadcrumb();

        parent::configure();
    }

    /**
     * The file a search engine reads, the page of the sitemap index when there is one. A path, since a route built
     * inside the admin carries its language.
     */
    protected function getOpenButton(): Stringable
    {
        $route = Url::to('@web/sitemap.xml');

        if (Sitemap::getComponent()->useSitemapIndex && $this->provider) {
            $route .= '?' . http_build_query(['key' => $this->key, 'offset' => $this->provider->offset]);
        }

        return Button::make()
            ->primary()
            ->icon('file-code')
            ->text(Yii::t('skeleton', 'SITEMAP_OPEN_BUTTON'))
            ->url($route)
            ->target('_blank');
    }

    protected function getSearchConsoleButton(): ?Stringable
    {
        $property = Yii::$app->params['googleSearchConsoleProperty'] ?? null;

        if (!is_string($property) || $property === '') {
            return null;
        }

        return Button::make()
            ->secondary()
            ->icon('magnifying-glass-chart')
            ->text(Yii::t('skeleton', 'DASHBOARD_SEARCH_CONSOLE_BUTTON'))
            ->url('https://search.google.com/search-console/sitemaps?resource_id=' . rawurlencode($property))
            ->target('_blank');
    }

    protected function addSystemBreadcrumb(): void
    {
        if (!$this->webuser->can(User::AUTH_ROLE_ADMIN) && !$this->webuser->can(User::AUTH_ROLE_MANAGER)) {
            return;
        }

        $this->addBreadcrumb(Yii::t('skeleton', 'SYSTEM_TITLE'), ['/admin/system/index']);
    }
}
