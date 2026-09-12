<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Search;

use Hirtz\Skeleton\Search\SearchText;
use Hirtz\Skeleton\Test\TestCase;

class SearchTextTest extends TestCase
{
    public function testNormalizeStripsTagsAndCollapsesWhitespace(): void
    {
        $html = "<h1>Hello</h1><p>World &amp; \n\n  friends</p>";
        self::assertSame('Hello World & friends', SearchText::normalize($html));
    }

    public function testNormalizeReturnsEmptyStringForNull(): void
    {
        self::assertSame('', SearchText::normalize(null));
    }

    public function testTransliterateOnlyReturnsTheFoldedTokens(): void
    {
        self::assertSame('Mueller Strasse', SearchText::transliterate('Müller GmbH Straße'));
        self::assertSame('', SearchText::transliterate('Miller Inc'));
    }

    public function testTokenizeDropsOperatorsAndShortTokens(): void
    {
        self::assertSame(['alte', 'firma'], SearchText::tokenize('+/alte-firma*'));
        self::assertSame([], SearchText::tokenize('IT VW'));
        self::assertSame([], SearchText::tokenize(null));
    }

    public function testToBooleanQueryRequiresEveryTokenAsPrefix(): void
    {
        self::assertSame('+alte* +firma*', SearchText::toBooleanQuery(['alte', 'firma']));
    }

    public function testSnippetWindowsAroundTheFirstMatch(): void
    {
        $text = str_repeat('lorem ipsum ', 20) . 'needle ' . str_repeat('dolor sit ', 20);
        $snippet = SearchText::snippet($text, ['needle'], 60);

        self::assertStringStartsWith('… ', $snippet);
        self::assertStringContainsString('needle', $snippet);
        self::assertLessThan(mb_strlen($text), mb_strlen($snippet));
    }

    public function testSnippetKeepsShortText(): void
    {
        self::assertSame('short', SearchText::snippet('short', ['short']));
    }
}
