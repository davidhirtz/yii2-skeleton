<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Helpers\Html;
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

    public function testAnEncodedAmpersandIsNotMarked(): void
    {
        $this->getWebRequest()->setQueryParams(['q' => 'a']);
        self::assertSame('Bl<mark>a</mark>ck &amp; White', (new GridSearch())->markKeywords('Black & White'));
    }

    public function testASecondKeywordDoesNotMatchInsideTheFirstMark(): void
    {
        $this->getWebRequest()->setQueryParams(['q' => 'hamburg mar']);
        self::assertSame('<mark>Hamburg</mark> <mark>Mar</mark>kt', (new GridSearch())->markKeywords('Hamburg Markt'));
        self::assertSame('<mark>Hamburg</mark> <mark>Mar</mark>kt &lt;Å&gt;', Html::markKeywords('Hamburg Markt <Å>', ['hamburg', 'mar']));
    }
}
