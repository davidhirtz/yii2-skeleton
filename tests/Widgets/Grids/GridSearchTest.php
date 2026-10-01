<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Grids\GridSearch;

class GridSearchTest extends TestCase
{
    public function testTheKeywordsAreMarked(): void
    {
        $this->getWebRequest()->setQueryParams(['q' => 'müll']);

        self::assertSame('<mark>Müll</mark>er', (new GridSearch())->markKeywords('Müller'));
    }

    public function testAnInvalidValueIsIgnored(): void
    {
        $this->getWebRequest()->setQueryParams(['q' => "\xFF"]);
        $search = new GridSearch();

        self::assertSame('', $search->getValue());
        self::assertSame('Müller', $search->markKeywords('Müller'));
    }
}
