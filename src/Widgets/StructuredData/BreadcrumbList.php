<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Script;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Widgets\Traits\BreadcrumbTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use yii\helpers\Json;

class BreadcrumbList extends Widget
{
    use BreadcrumbTrait;

    protected function renderContent(): string|Stringable
    {
        return Script::make()
            ->type('application/ld+json')
            ->content($this->getScriptContent());
    }

    protected function getScriptContent(): string
    {
        return Json::htmlEncode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $this->getItemListElement(),
        ]);
    }

    protected function getItemListElement(): array
    {
        return array_filter(array_map($this->getListItem(...), $this->breadcrumbs, array_keys($this->breadcrumbs)));
    }

    protected function getListItem(Breadcrumb $breadcrumb, int $index): array
    {
        return $breadcrumb->url
            ? [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $breadcrumb->label,
                'item' => Url::to($breadcrumb->url, true),
            ]
            : [];
    }
}
