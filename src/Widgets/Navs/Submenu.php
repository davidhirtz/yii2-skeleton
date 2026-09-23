<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class Submenu extends Widget
{
    /** @use ItemTrait<NavItem> */
    use ItemTrait;

    use TagContentTrait;
    use ContainerTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $navAttributes = ['class' => 'tabs', 'data-scroll-active' => true];
    protected bool $hideSingleItem = true;

    /**
     * @var array<int|string, mixed>|string|false|null `null` lets a submenu derive it, `false` shows none
     */
    protected array|string|false|null $backUrl = null;

    public function title(): void
    {

    }

    public function hideSingleItem(bool $hideSingleItem): static
    {
        $this->hideSingleItem = $hideSingleItem;
        return $this;
    }

    /**
     * @param array<int|string, mixed>|string|false|null $url
     */
    public function backUrl(array|string|false|null $url): static
    {
        $this->backUrl = $url;
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

        $back = $this->getBackItem();

        if (!$visibleCount || ($this->hideSingleItem && $visibleCount < 2 && !$back)) {
            return '';
        }

        return Nav::make()
            ->attributes($this->navAttributes)
            ->items([$back, ...$this->items]);
    }

    protected function getBackItem(): ?NavItem
    {
        if (!$this->backUrl) {
            return null;
        }

        $label = Yii::t('skeleton', 'SUBMENU_BACK');

        return NavItem::make()
            ->icon('arrow-left')
            ->url($this->backUrl)
            ->link(fn (A $link): A => $link
                ->addClass('nav-back-link')
                ->addAttributes([
                    'aria-label' => $label,
                    'data-tooltip' => '',
                    'title' => $label,
                ]));
    }

    /**
     * The listing the record appears in, or its parent's own page where it has none: one level up the tree the
     * submenu replaced. A record without an admin parent is a root and needs no way back.
     *
     * @return array<int|string, mixed>|string|null
     */
    protected function getAdminBackUrl(AdminModelInterface $model): array|string|null
    {
        $parent = $model->getAdminParent();

        if (!$parent) {
            return null;
        }

        return $model->getAdminIndexBreadcrumb()->url ?? ($parent->getAdminRoute() ?: null);
    }
}
