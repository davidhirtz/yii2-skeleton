<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

use Hirtz\Skeleton\Test\Browser;
use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerAnySelectorTextContains;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerAnySelectorTextSame;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorAttributeValueSame;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorExists;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorTextContains;
use Symfony\Component\DomCrawler\Test\Constraint\CrawlerSelectorTextSame;

use function sprintf;

trait FunctionalTestTrait
{
    protected static Browser $browser;
    protected static Crawler $crawler;

    protected static function openUri(string $uri, array $parameters = []): void
    {
        self::$browser = new Browser();
        self::$crawler = self::$browser->request('GET', $uri, $parameters);
    }

    public static function assertResponseIsSuccessful(): void
    {
        self::assertResponseStatusCodeSame(200);
    }

    public static function assertResponseStatusCodeSame(int $expected): void
    {
        $code = self::$browser->getResponse()->getStatusCode();
        self::assertEquals($expected, $code, "Expected response code $expected, got $code.");
    }

    public static function assertUrlPathEquals(string $expected): void
    {
        $path = parse_url(self::$crawler->getUri(), PHP_URL_PATH);
        $expected = '/' . ltrim($expected, '/');

        self::assertEquals($expected, $path, "Expected query path '$expected', got '$path'.");
    }

    public static function assertAnyAlertErrorSame(string $text): void
    {
        self::assertAlertSame($text, 'error');
    }

    public static function assertAlertSame(string $text, ?string $type = null): void
    {
        $selector = $type ? "[data-alert=\"$type\"] .alert-content" : '.alert-content';
        self::assertAnySelectorTextSame($selector, $text);
    }

    public static function assertAnyValidationErrorSame(string $text): void
    {
        self::assertAnySelectorTextSame('.form-error', $text);
    }

    public static function assertSelectorExists(string $selector, string $message = ''): void
    {
        self::assertThat(self::$crawler, new CrawlerSelectorExists($selector), $message);
    }

    public static function assertSelectorNotExists(string $selector, string $message = ''): void
    {
        self::assertThat(self::$crawler, new LogicalNot(new CrawlerSelectorExists($selector)), $message);
    }

    public static function assertSelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new CrawlerSelectorTextContains($selector, $text)
        ), $message);
    }

    public static function assertAnySelectorTextContains(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new CrawlerAnySelectorTextContains($selector, $text)
        ), $message);
    }

    public static function assertSelectorTextSame(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new CrawlerSelectorTextSame($selector, $text)
        ), $message);
    }

    public static function assertAnySelectorTextSame(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new CrawlerAnySelectorTextSame($selector, $text)
        ), $message);
    }

    public static function assertSelectorTextNotContains(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new LogicalNot(new CrawlerSelectorTextContains($selector, $text))
        ), $message);
    }

    public static function assertAnySelectorTextNotContains(string $selector, string $text, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists($selector),
            new LogicalNot(new CrawlerAnySelectorTextContains($selector, $text))
        ), $message);
    }

    public static function assertPageTitleSame(string $expectedTitle, string $message = ''): void
    {
        self::assertSelectorTextSame('title', $expectedTitle, $message);
    }

    public static function assertPageTitleContains(string $expectedTitle, string $message = ''): void
    {
        self::assertSelectorTextContains('title', $expectedTitle, $message);
    }

    public static function assertInputValueSame(string $fieldName, string $expectedValue, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists("input[name=\"$fieldName\"]"),
            new CrawlerSelectorAttributeValueSame("input[name=\"$fieldName\"]", 'value', $expectedValue)
        ), $message);
    }

    public static function assertInputValueNotSame(string $fieldName, string $expectedValue, string $message = ''): void
    {
        self::assertThat(self::$crawler, LogicalAnd::fromConstraints(
            new CrawlerSelectorExists("input[name=\"$fieldName\"]"),
            new LogicalNot(new CrawlerSelectorAttributeValueSame("input[name=\"$fieldName\"]", 'value', $expectedValue))
        ), $message);
    }

    public static function assertCheckboxChecked(string $fieldName, string $message = ''): void
    {
        self::assertThat(self::$crawler, new CrawlerSelectorExists("input[name=\"$fieldName\"]:checked"), $message);
    }

    public static function assertCheckboxNotChecked(string $fieldName, string $message = ''): void
    {
        self::assertThat(self::$crawler, new LogicalNot(new CrawlerSelectorExists("input[name=\"$fieldName\"]:checked")), $message);
    }

    public static function assertFormValue(string $formSelector, string $fieldName, string $value, string $message = ''): void
    {
        $node = self::$crawler->filter($formSelector);
        self::assertNotEmpty($node, sprintf('Form "%s" not found.', $formSelector));

        $values = $node->form()->getValues();
        self::assertArrayHasKey($fieldName, $values, $message ?: sprintf('Field "%s" not found in form "%s".', $fieldName, $formSelector));
        self::assertSame($value, $values[$fieldName]);
    }

    public static function assertNoFormValue(string $formSelector, string $fieldName, string $message = ''): void
    {
        $node = self::$crawler->filter($formSelector);
        self::assertNotEmpty($node, sprintf('Form "%s" not found.', $formSelector));

        $values = $node->form()->getValues();
        self::assertArrayNotHasKey($fieldName, $values, $message ?: sprintf('Field "%s" has a value in form "%s".', $fieldName, $formSelector));
    }
}
