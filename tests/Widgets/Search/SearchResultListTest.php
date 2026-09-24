<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Search;

use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;

class SearchResultListTest extends TestCase
{
    public function testSubtitlesFollowTheTitle(): void
    {
        $html = (string)SearchResultList::make()
            ->query('about')
            ->results([new SearchResult('About', subtitles: ['Section 2/5', 'Asset 1/2'])]);

        self::assertStringContainsString(
            '<span class="search-result-heading"><span class="search-result-title"><mark>About</mark></span>'
            . '<span class="search-result-subtitle">Section 2/5</span>'
            . '<span class="search-result-subtitle">Asset 1/2</span></span>',
            $html
        );
    }

    public function testATitleWithoutSubtitlesStandsAlone(): void
    {
        $html = (string)SearchResultList::make()->results([new SearchResult('About')]);

        self::assertStringContainsString('<span class="search-result-title">About</span>', $html);
        self::assertStringNotContainsString('search-result-heading', $html);
    }
}
