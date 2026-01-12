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
use yii\base\Model;

use function sprintf;

trait FunctionalTestTrait
{
    protected static Browser $client;
    protected static Crawler $crawler;

    protected function open(string $uri, array $parameters = []): void
    {
        $host = parse_url($uri, PHP_URL_HOST) ?: 'www.test.localhost';

        self::$client = new Browser([
            'HTTP_HOST' => $host,
            'HTTPS' => 'on',
        ]);

        self::$crawler = self::$client->request('GET', $uri, $parameters);
    }

    protected function getJsonResponseData(): array
    {
        self::assertResponseIsJson();
        return json_decode(self::$client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function click(string $selector): void
    {
        self::assertSelectorExists($selector);

        $link = self::$crawler->filter($selector)->link();
        self::$crawler = self::$client->click($link);
    }

    protected function submit(string $selector = 'form', array $values = []): void
    {
        self::assertSelectorExists($selector);

        $form = self::$crawler->filter($selector)->form($values);
        self::$crawler = self::$client->submit($form);
    }

    protected function prefixFormValues(string|Model $prefix, array $values): array
    {
        $keys = array_map(fn ($key) => sprintf('%s[%s]', is_string($prefix) ? $prefix : $prefix->formName(), $key), array_keys($values));
        return array_combine($keys, array_values($values));
    }

    public static function assertResponseIsSuccessful(): void
    {
        self::assertResponseStatusCodeSame(200);
    }


    public static function assertResponseIsJson(): void
    {
        $headerName = 'content-type';

        self::assertResponseHasHeader($headerName);
        self::assertStringContainsString('application/json', self::$client->getResponse()->getHeaders()[$headerName][0]);
    }

    public static function assertResponseStatusCodeSame(int $expected): void
    {
        $code = self::$client->getResponse()->getStatusCode();
        self::assertEquals($expected, $code, "Expected response code $expected, got $code.");
    }

    public static function assertCurrentUrlEquals(string $expected): void
    {
        if (preg_match('~^https?://~', $expected)) {
            $url = self::$crawler->getUri();
            self::assertEquals($expected, $url, "Expected current URL '$expected', got '$url'.");
            return;
        }

        $path = parse_url((string)self::$crawler->getUri(), PHP_URL_PATH);
        $expected = '/' . ltrim($expected, '/');

        self::assertEquals($expected, $path, "Expected query path '$expected', got '$path'.");
    }

    public static function assertResponseHasHeader(string $headerName, string $message = ''): void
    {
        self::assertContains($headerName, array_keys(self::$client->getResponse()->getHeaders()), $message);
    }

    public static function assertResponseNotHasHeader(string $headerName, string $message = ''): void
    {
        self::assertNotContains($headerName, array_keys(self::$client->getResponse()->getHeaders()), $message);
    }

    public static function assertResponseHeaderSame(string $headerName, string $expectedValue, string $message = ''): void
    {
        self::assertResponseHasHeader($headerName, $message);
        self::assertEquals($expectedValue, self::$client->getResponse()->getHeaders()[$headerName][0], $message);
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
