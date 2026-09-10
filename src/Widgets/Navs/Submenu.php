<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Submenu extends Widget
{
    /** @use ItemTrait<NavItem> */
    use ItemTrait;

    use TagContentTrait;
    use ContainerTrait;

    protected array $navAttributes = ['class' => 'tabs'];
    protected bool $hideSingleItem = true;

    public function title(): void
    {

    }

    public function hideSingleItem(bool $hideSingleItem): static
    {
        $this->hideSingleItem = $hideSingleItem;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getContent();
    }

    protected function getContent(): string|Stringable
    {
        $visibleCount = count(array_filter($this->items, static fn (NavItem $item): bool => $item->isVisible()));

        if (!$visibleCount || ($this->hideSingleItem && $visibleCount < 2)) {
            return '';
        }

        return Nav::make()
            ->attributes($this->navAttributes)
            ->items($this->items);
    }
}
