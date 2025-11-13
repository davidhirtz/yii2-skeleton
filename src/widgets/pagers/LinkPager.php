<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\pagers;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;

class LinkPager extends \yii\widgets\LinkPager
{
    public array $pageOptions = [];
    public string $linkCssClass = 'page-link';
    public bool $renderDisabledLink = true;

    public $maxButtonCount = 5;
    public $pageCssClass = 'page-item';
    public $firstPageLabel = true;
    public $lastPageLabel = true;

    public $options = [
        'class' => 'pagination',
        'hx-boost' => 'true',
        'hx-swap' => 'scroll:top',
    ];

    #[\Override]
    public function init(): void
    {
        if ($this->pageCssClass) {
            Html::addCssClass($this->pageOptions, $this->pageCssClass);
        }

        if ($this->linkCssClass) {
            Html::addCssClass($this->linkOptions, $this->linkCssClass);
        }

        parent::init();
    }

    #[\Override]
    protected function renderPageButtons(): string
    {
        if ($this->pagination->getPageCount() > 1 || !$this->hideOnSinglePage) {
            $currentPage = $this->pagination->getPage();
            $lastPage = $this->pagination->getPageCount() - 1;
            [$beginPage, $endPage] = $this->getPageRange();

            $buttons = [];
            $buttons[] = $this->renderPrevPageButton();

            if ($beginPage > 0) {
                $buttons[] = $this->renderFirstPageButton();
                $buttons[] = $this->renderRangeButton(0, $beginPage);
            }

            for ($i = $beginPage; $i <= $endPage; ++$i) {
                $buttons[] = $this->renderPageButton((string)($i + 1), $i, '', false, $i === $currentPage);
            }

            if ($endPage < $lastPage) {
                $buttons[] = $this->renderRangeButton($endPage, $lastPage);
                $buttons[] = $this->renderLastPageButton();
            }

            $buttons[] = $this->renderNextPageButton();

            return Html::tag('ul', implode('', $buttons), $this->options);
        }

        return '';
    }

    #[\Override]
    protected function renderPageButton($label, $page, $class, $disabled, $active): string
    {
        $options = $this->pageOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'li');

        if ($class) {
            Html::addCssClass($options, $class);
        }

        if ($active) {
            Html::addCssClass($options, $this->activePageCssClass);
        }

        if ($disabled) {
            Html::addCssClass($options, $this->disabledPageCssClass);

            if (!$this->renderDisabledLink) {
                return Html::tag($tag, Html::tag('span', $label), $options);
            }
        }

        $linkOptions = $this->linkOptions;

        return Html::tag($tag, Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
    }

    public function renderFirstPageButton(): string
    {
        $label = $this->firstPageLabel === true ? '1' : $this->firstPageLabel;

        return ($label !== false && $this->pagination->getPage() > 0)
            ? $this->renderPageButton($label, 0, $this->firstPageCssClass, false, false)
            : '';
    }

    public function renderLastPageButton(): string
    {
        $pageCount = $this->pagination->getPageCount();
        $label = $this->lastPageLabel === true ? $pageCount : $this->lastPageLabel;

        return $label !== false
            ? $this->renderPageButton($label, $pageCount - 1, $this->lastPageCssClass, $this->pagination->getPage() >= $pageCount - 1, false)
            : '';
    }

    public function renderPrevPageButton(string|false|null $label = null, ?string $cssClass = null): string
    {
        $label ??= $this->prevPageLabel;

        if (!$label) {
            return '';
        }

        $currentPage = $this->pagination->getPage();

        if (($page = $currentPage - 1) < 0) {
            $page = 0;
        }

        return $this->renderPageButton($label, $page, $cssClass ?: $this->prevPageCssClass, $currentPage <= 0, false);
    }

    public function renderNextPageButton(string|false|null $label = null, ?string $cssClass = null): string
    {
        $label ??= $this->nextPageLabel;

        if (!$label) {
            return '';
        }

        $currentPage = $this->pagination->getPage();
        $pageCount = $this->pagination->getPageCount();

        if (($page = $currentPage + 1) >= $pageCount - 1) {
            $page = $pageCount - 1;
        }

        return $this->renderPageButton($label, $page, $cssClass ?: $this->nextPageCssClass, $currentPage >= $pageCount - 1, false);
    }

    public function renderRangeButton(int $start, int $stop): string
    {
        if ($start < $stop - 1) {
            $ellipsis = $start < $stop - 2;
            return $this->renderPageButton($ellipsis ? '...' : $start + 2, $start + 1, '', $ellipsis, false);
        }

        return '';
    }
}
