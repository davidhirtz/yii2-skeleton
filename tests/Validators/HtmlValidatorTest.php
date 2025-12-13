<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\HtmlValidator;

class HtmlValidatorTest extends TestCase
{
    protected array $defaultAllowedHtmlTags = ['a', 'br', 'li', 'ol', 'p', 'span', 'strong', 'ul'];
    protected array $defaultAllowedHtmlAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    public function testDefaultConfig(): void
    {
        $validator = new HtmlValidator();

        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlAttributes, $validator->allowedHtmlAttributes);

        static::assertStringContainsString('a[href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']);
    }

    public function testConfigWithAdditionalHtmlTags(): void
    {
        $additionalHtmlTags = ['h2', 'h3'];
        $allowedHtmlTags = [...$this->defaultAllowedHtmlTags, ...$additionalHtmlTags];

        $validator = new HtmlValidator([
            'allowedHtmlTags' => $additionalHtmlTags,
        ]);

        static::assertEqualsCanonicalizing($allowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlAttributes, $validator->allowedHtmlAttributes);
    }

    public function testConfigWithAdditionalLegacyHtmlTags(): void
    {
        $validator = new HtmlValidator([
            'allowedHtmlTags' => ['a[href|target|rel]', 'H2'],
        ]);

        $expectedAllowedHtmlTags = [...$this->defaultAllowedHtmlTags, 'h2'];

        static::assertEqualsCanonicalizing($expectedAllowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing(['href', 'rel', 'target'], $validator->allowedHtmlAttributes['a'] ?? []);
        static::assertStringContainsString('a[href|rel|target]', $validator->purifierOptions['HTML.Allowed']);
    }

    public function testConfigWithExcludedHtmlTags(): void
    {
        $excludedHtmlTags = ['a', 'strong'];
        $allowedHtmlTags = array_diff($this->defaultAllowedHtmlTags, $excludedHtmlTags);

        $validator = new HtmlValidator([
            'excludedHtmlTags' => $excludedHtmlTags,
        ]);

        static::assertEqualsCanonicalizing($allowedHtmlTags, $validator->allowedHtmlTags);
        static::assertStringNotContainsString('a[href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']);
    }

    public function testConfigWithAllowedClasses(): void
    {
        $allowedClasses = [
            'a' => ['btn', 'cta'],
            'span' => [
                'Named 1' => 'marked',
                'Named 2' => 'highlight',
            ],
        ];

        $validator = new HtmlValidator([
            'allowedClasses' => $allowedClasses,
        ]);

        static::assertEqualsCanonicalizing(['marked', 'highlight'], $validator->allowedClasses['span'] ?? []);
        static::assertEqualsCanonicalizing(['btn', 'cta', 'marked', 'highlight'], $validator->purifierOptions['Attr.AllowedClasses']);
        static::assertStringContainsString('a[class|href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']);
        static::assertStringContainsString('span[class]', $validator->purifierOptions['HTML.Allowed']);
    }

    public function testConfigWithAllowedClassesLegacyLink(): void
    {
        $allowedClasses = ['btn', 'cta'];

        $validator = new HtmlValidator([
            'allowedClasses' => $allowedClasses,
        ]);

        static::assertEqualsCanonicalizing($validator->allowedClasses, ['a' => $allowedClasses]);
        static::assertEqualsCanonicalizing($allowedClasses, $validator->purifierOptions['Attr.AllowedClasses']);
        static::assertStringContainsString('a[class|href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']);
    }

    public function testConfigWithAllowImagesWithAttributes(): void
    {
        $validator = new HtmlValidator([
            'allowImages' => true,
            'allowedHtmlAttributes' => [
                'img' => ['src', 'alt'],
            ],
        ]);

        static::assertEqualsCanonicalizing(['alt', 'src'], $validator->allowedHtmlAttributes['img'] ?? []);
        static::assertStringContainsString('img[alt|src]', $validator->purifierOptions['HTML.Allowed']);
    }
}
