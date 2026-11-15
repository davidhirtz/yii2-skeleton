<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Pagers;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use yii\data\Pagination;

class LinkPager extends Widget
{
    use TagAttributesTrait;

    protected Pagination $pagination;
    protected int $maxButtonCount = 5;
    protected bool $hideOnSinglePage = true;
    protected bool $showFirstAndLastPage = true;
    protected ?string $prevPageLabel = '&laquo;';
    protected ?string $nextPageLabel = '&raquo;';

    public function pagination(Pagination $pagination): static
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function maxButtonCount(int $maxButtonCount): static
    {
        $this->maxButtonCount = $maxButtonCount;
        return $this;
    }

    public function hideOnSinglePage(bool $hideOnSinglePage = true): static
    {
        $this->hideOnSinglePage = $hideOnSinglePage;
        return $this;
    }

    public function showFirstAndLastPage(bool $showFirstAndLastPage = true): static
    {
        $this->showFirstAndLastPage = $showFirstAndLastPage;
        return $this;
    }

    /**
     * The labels are HTML, `null` hides the button.
     */
    public function prevAndNextPageLabels(?string $prevPageLabel, ?string $nextPageLabel): static
    {
        $this->prevPageLabel = $prevPageLabel;
        $this->nextPageLabel = $nextPageLabel;

        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->addClass('pagination');

        // `:inherited`: these sit on the pagination list, its links are what issue the request. A swap without a
        // style falls back to `innerHTML`, which nests the response's `#wrap` inside the page's.
        $this->attributes['hx-boost:inherited'] ??= 'true';
        $this->attributes['hx-swap:inherited'] ??= 'outerHTML scroll:top';

        parent::configure();
    }

    #[Override]
    public function isVisible(): bool
    {
        return (!$this->hideOnSinglePage || $this->pagination->getPageCount() > 1) && parent::isVisible();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $currentPage = $this->pagination->getPage();
        $lastPage = $this->pagination->getPageCount() - 1;
        [$beginPage, $endPage] = $this->getPageRange();

        $items = [$this->getPrevPageItem()];

        if ($beginPage > 0) {
            $items[] = $this->getFirstPageItem();
            $items[] = $this->getRangeItem(0, $beginPage);
        }

        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $items[] = $this->getPageItem((string)($i + 1), $i, active: $i === $currentPage);
        }

        if ($endPage < $lastPage) {
            $items[] = $this->getRangeItem($endPage, $lastPage);
            $items[] = $this->getLastPageItem();
        }

        $items[] = $this->getNextPageItem();

        return Ul::make()
            ->attributes($this->attributes)
            ->content(...$items);
    }

    protected function getPageItem(string $label, int $page, ?string $class = null, bool $disabled = false, bool $active = false): Li
    {
        return Li::make()
            ->class('page-item', $class, $active ? 'active' : null, $disabled ? 'disabled' : null)
            ->content(A::make()
                ->class('page-link')
                ->href($this->pagination->createUrl($page))
                ->content($label));
    }

    protected function getFirstPageItem(): ?Li
    {
        return $this->showFirstAndLastPage && $this->pagination->getPage() > 0
            ? $this->getPageItem('1', 0, 'first')
            : null;
    }

    protected function getLastPageItem(): ?Li
    {
        $pageCount = $this->pagination->getPageCount();

        return $this->showFirstAndLastPage
            ? $this->getPageItem((string)$pageCount, $pageCount - 1, 'last', $this->pagination->getPage() >= $pageCount - 1)
            : null;
    }

    protected function getPrevPageItem(): ?Li
    {
        if ($this->prevPageLabel === null) {
            return null;
        }

        $currentPage = $this->pagination->getPage();

        return $this->getPageItem($this->prevPageLabel, max(0, $currentPage - 1), 'prev', $currentPage <= 0);
    }

    protected function getNextPageItem(): ?Li
    {
        if ($this->nextPageLabel === null) {
            return null;
        }

        $currentPage = $this->pagination->getPage();
        $lastPage = $this->pagination->getPageCount() - 1;

        return $this->getPageItem($this->nextPageLabel, min($lastPage, $currentPage + 1), 'next', $currentPage >= $lastPage);
    }

    protected function getRangeItem(int $start, int $stop): ?Li
    {
        if ($start >= $stop - 1) {
            return null;
        }

        $ellipsis = $start < $stop - 2;

        return $this->getPageItem($ellipsis ? '...' : (string)($start + 2), $start + 1, disabled: $ellipsis);
    }

    /**
     * @return array{int, int}
     */
    protected function getPageRange(): array
    {
        $pageCount = $this->pagination->getPageCount();
        $beginPage = max(0, $this->pagination->getPage() - intdiv($this->maxButtonCount, 2));
        $endPage = $beginPage + $this->maxButtonCount - 1;

        if ($endPage >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }
}
