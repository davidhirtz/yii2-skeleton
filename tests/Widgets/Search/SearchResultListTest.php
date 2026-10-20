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
            '<span class="model-heading"><span class="model-heading-title"><mark>About</mark></span>'
            . '<span class="model-heading-subtitle">Section 2/5</span>'
            . '<span class="model-heading-subtitle">Asset 1/2</span></span>',
            $html
        );
    }

    public function testATitleWithoutSubtitlesStandsAlone(): void
    {
        $html = (string)SearchResultList::make()->results([new SearchResult('About')]);

        self::assertStringContainsString('<span class="model-heading-title">About</span>', $html);
        self::assertStringNotContainsString('class="model-heading"', $html);
    }
}
