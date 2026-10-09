<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Widgets\Traits\BreadcrumbTrait;
use Override;

class BreadcrumbList extends Thing
{
    use BreadcrumbTrait;

    protected ?string $type = 'BreadcrumbList';

    #[Override]
    protected function getProperties(): array
    {
        return [
            'itemListElement' => $this->getItemListElement(),
            ...parent::getProperties(),
        ];
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

            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $breadcrumb->label,
                'item' => $breadcrumb->url ? Url::to($breadcrumb->url, true) : null,
            ];
        }

        return $items;
    }
}
