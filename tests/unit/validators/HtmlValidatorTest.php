<?php

namespace davidhirtz\yii2\skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;

class HtmlValidatorTest extends Unit
{
    protected UnitTester $tester;

    protected array $defaultAllowedHtmlTags = ['a', 'br', 'li', 'ol', 'p', 'span', 'strong', 'ul'];
    protected array $defaultAllowedHtmlAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    public function testDefaultConfig()
    {
        $validator = new HtmlValidator();

        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlAttributes, $validator->allowedHtmlAttributes);

        static::assertTrue(in_array('a[href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']));
    }

    public function testSetAdditionalHtmlTags()
    {
        $additionalHtmlTags = ['h2', 'h3'];
        $allowedHtmlTags = array_merge($this->defaultAllowedHtmlTags, $additionalHtmlTags);

        $validator = new HtmlValidator([
            'allowedHtmlTags' => $additionalHtmlTags,
        ]);

        static::assertEqualsCanonicalizing($allowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing($this->defaultAllowedHtmlAttributes, $validator->allowedHtmlAttributes);
    }

    public function testSetAdditionalLegacyHtmlTags()
    {
        $validator = new HtmlValidator([
            'allowedHtmlTags' => ['a[href|target|rel]', 'H2'],
        ]);

        $expectedAllowedHtmlTags = array_merge($this->defaultAllowedHtmlTags, ['h2']);

        static::assertEqualsCanonicalizing($expectedAllowedHtmlTags, $validator->allowedHtmlTags);
        static::assertEqualsCanonicalizing(['href', 'rel', 'target'], $validator->allowedHtmlAttributes['a'] ?? []);
        static::assertTrue(in_array('a[href|rel|target]', $validator->purifierOptions['HTML.Allowed']));
    }

    public function testSetExcludedHtmlTags()
    {
        $excludedHtmlTags = ['a', 'strong'];
        $allowedHtmlTags = array_diff($this->defaultAllowedHtmlTags, $excludedHtmlTags);

        $validator = new HtmlValidator([
            'excludedHtmlTags' => $excludedHtmlTags,
        ]);

        static::assertEqualsCanonicalizing($allowedHtmlTags, $validator->allowedHtmlTags);
        static::assertFalse(in_array('a[href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']));
    }

    public function testSetAllowedClasses()
    {
        $allowedClasses = ['btn', 'cta'];

        $validator = new HtmlValidator([
            'allowedClasses' => $allowedClasses,
        ]);

        static::assertEqualsCanonicalizing($allowedClasses, $validator->allowedClasses);
        static::assertTrue(in_array('a[class|href|rel|target|title]', $validator->purifierOptions['HTML.Allowed']));
        static::assertTrue(in_array('span[class]', $validator->purifierOptions['HTML.Allowed']));
    }

    public function testSetAllowImagesWithAttributes()
    {
        $validator = new HtmlValidator([
            'allowImages' => true,
            'allowedHtmlAttributes' => [
                'img' => ['src', 'alt'],
            ],
        ]);

        static::assertEqualsCanonicalizing(['alt', 'src'], $validator->allowedHtmlAttributes['img'] ?? []);
        static::assertTrue(in_array('img[alt|src]', $validator->purifierOptions['HTML.Allowed']));
    }
}