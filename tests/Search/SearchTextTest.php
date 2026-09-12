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

    public function testTokenizeKeepsEveryWordAndDropsTheOperators(): void
    {
        self::assertSame(['alte', 'firma'], SearchText::tokenize('+/alte-firma*'));
        self::assertSame(['IT', 'VW'], SearchText::tokenize('IT VW'));
        self::assertSame(['hausmeister', 'domain', 'com'], SearchText::tokenize('hausmeister@domain.com'));
        self::assertSame([], SearchText::tokenize(null));
    }

    public function testIsIndexedFollowsInnoDb(): void
    {
        self::assertTrue(SearchText::isIndexed('firma'));
        self::assertFalse(SearchText::isIndexed('com'), '`com` is one of InnoDB\'s stopwords.');
        self::assertFalse(SearchText::isIndexed('IT'), 'Two characters are below the minimum token size.');
    }

    /**
     * The prefixed copies are the only way a stopword or a short token reaches the index at all.
     */
    public function testGetIndexTokensCarriesWhatInnoDbWouldDrop(): void
    {
        self::assertSame('zzcom', SearchText::getIndexTokens('hausmeister@domain.com'));
        self::assertSame('zzit zzvw', SearchText::getIndexTokens('IT and VW'));
        self::assertSame('Mueller zzund', SearchText::getIndexTokens('Müller und Sohn'));
        self::assertSame('', SearchText::getIndexTokens('Bergfirma Sohn'));
    }

    /**
     * A token InnoDB indexes is an ordinary prefix; one it does not is either its copy or the plain prefix, so
     * `com` finds `domain.com` as well as `commerce`.
     */
    public function testToBooleanQueryRequiresEveryTokenAsPrefix(): void
    {
        self::assertSame('+alte* +firma*', SearchText::toBooleanQuery(['alte', 'firma']));
        self::assertSame('+domain* +(zzcom* com*)', SearchText::toBooleanQuery(['domain', 'com']));
        self::assertSame('+(zzit* IT*)', SearchText::toBooleanQuery(['IT']));
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
