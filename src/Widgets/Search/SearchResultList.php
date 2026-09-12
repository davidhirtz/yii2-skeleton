<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Search;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Search\SearchText;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class SearchResultList extends Widget
{
    use TagAttributesTrait;

    /**
     * @var list<SearchResult>
     */
    protected array $results = [];

    protected ?string $query = null;
    protected ?string $emptyText = null;
    protected int $snippetLength = 160;

    /**
     * @param list<SearchResult> $results
     */
    public function results(array $results): static
    {
        $this->results = $results;
        return $this;
    }

    public function query(?string $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function emptyText(?string $emptyText): static
    {
        $this->emptyText = $emptyText;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $items = $this->results
            ? array_map($this->getResultItem(...), $this->results)
            : [$this->getEmptyItem()];

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('search-results')
            ->content(...$items);
    }

    protected function getResultItem(SearchResult $result): Stringable
    {
        $link = A::make()
            ->class('search-result')
            ->href($result->route ?: $result->url);

        if ($result->icon) {
            $link->addContent(Icon::make()
                ->name($result->icon)
                ->addClass('search-result-icon'));
        }

        $text = Div::make()
            ->class('search-result-text')
            ->content(Span::make()
                ->class('search-result-title')
                ->content($this->markKeywords($result->title)));

        if ($result->description) {
            $text->addContent(Span::make()
                ->class('search-result-description')
                ->content($this->markKeywords($this->getSnippet($result->description))));
        }

        $link->addContent($text);

        if ($result->badge) {
            $link->addContent(Span::make()
                ->class('search-result-badge')
                ->text($result->badge));
        }

        return Li::make()
            ->class('search-result-item')
            ->content($link);
    }

    protected function getEmptyItem(): Stringable
    {
        return Li::make()
            ->class('search-result-empty')
            ->text($this->emptyText ?? Yii::t('skeleton', 'SEARCH_EMPTY'));
    }

    protected function getSnippet(string $description): string
    {
        return SearchText::snippet($description, $this->getKeywords(), $this->snippetLength);
    }

    protected function markKeywords(string $text): string
    {
        return Html::markKeywords(Html::encode($text), $this->getKeywords());
    }

    /**
     * @return list<string>
     */
    protected function getKeywords(): array
    {
        return SearchText::tokenize($this->query) ?: array_values(array_filter(explode(' ', trim((string)$this->query))));
    }
}
