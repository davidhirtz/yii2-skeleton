<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Script;
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

    /**
     * A crumb without a URL is left out, unless it is the last one: the current page, which schema.org lists
     * without an `item`. Positions count the listed crumbs only.
     *
     * @return list<array<string, mixed>>
     */
    protected function getItemListElement(): array
    {
        $breadcrumbs = array_values($this->getBreadcrumbs());
        $last = array_key_last($breadcrumbs);
        $items = [];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            if (!$breadcrumb->url && $index !== $last) {
                continue;
            }

            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $breadcrumb->label,
                'item' => $breadcrumb->url ? Url::to($breadcrumb->url, true) : null,
            ], fn (mixed $value): bool => $value !== null);
        }

        return $items;
    }
}
