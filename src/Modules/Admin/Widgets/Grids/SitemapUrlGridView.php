<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Modules\Admin\Data\SitemapUrlArrayDataProvider;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Stringable;
use Yii;

/**
 * @phpstan-import-type SitemapUrl from SitemapUrlArrayDataProvider
 * @extends GridView<SitemapUrl>
 * @property SitemapUrlArrayDataProvider|null $provider
 */
class SitemapUrlGridView extends GridView
{
    #[Override]
    public function configure(): void
    {
        $this->columns ??= [
            $this->getLocColumn(),
            $this->provider?->hasImages() ? $this->getImageCountColumn() : null,
            $this->getLastmodColumn(),
        ];

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getItems() ?? Alert::make()
            ->warning()
            ->text(Yii::t('skeleton', 'SITEMAP_GRID_EMPTY'));
    }

    protected function getLocColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('loc')
            ->title(Yii::t('skeleton', 'SITEMAP_URL_LABEL'))
            ->url(fn (array $url): string => $url['loc'])
            ->target('_blank');
    }

    protected function getImageCountColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('imageCount')
            ->title(Yii::t('skeleton', 'SITEMAP_IMAGES_LABEL'))
            ->format('integer');
    }

    protected function getLastmodColumn(): RelativeTimeColumn
    {
        return RelativeTimeColumn::make()
            ->property('lastmod')
            ->title(Yii::t('skeleton', 'MODEL_UPDATED_AT_LABEL'));
    }
}
