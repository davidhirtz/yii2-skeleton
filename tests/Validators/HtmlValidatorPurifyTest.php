<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\HtmlValidator;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIScheme;
use Override;

/**
 * Covers what the validator does to the value; {@see HtmlValidatorTest} covers how it is configured.
 */
class HtmlValidatorPurifyTest extends TestCase
{
    public function testAScriptTagIsRemoved(): void
    {
        $html = $this->purify('<p>Hello</p><script>alert(1)</script>');

        self::assertStringNotContainsString('script', $html);
        self::assertStringContainsString('<p>Hello</p>', $html);
    }

    public function testALinkKeepsItsAriaLabelAndNoOtherAriaAttribute(): void
    {
        $html = $this->purify('<p><a href="/karriere" aria-label="Mehr: Ågot 東京 🚀" aria-hidden="true">Mehr</a></p>');

        self::assertSame('<p><a href="/karriere" aria-label="Mehr: Ågot 東京 🚀">Mehr</a></p>', $html);
    }

    public function testAnAriaLabelIsKeptOnLinksOnly(): void
    {
        self::assertSame('<p>Text</p>', $this->purify('<p aria-label="Nope">Text</p>'));
    }

    public function testAnEventHandlerAttributeIsRemoved(): void
    {
        $html = $this->purify('<p onclick="alert(1)">Hello</p>');

        self::assertSame('<p>Hello</p>', $html);
    }

    public function testAJavascriptHrefIsRemoved(): void
    {
        $html = $this->purify('<a href="javascript:alert(1)">Click</a>');

        self::assertStringNotContainsString('javascript', $html);
    }

    public function testAnIframeIsRemoved(): void
    {
        $html = $this->purify('<p>Before</p><iframe src="https://evil.test"></iframe>');

        self::assertSame('<p>Before</p>', $html);
    }

    public function testATagThatIsNotAllowedLosesItsMarkupButKeepsItsText(): void
    {
        $html = $this->purify('<h2>Heading</h2>');

        self::assertStringNotContainsString('<h2>', $html);
        self::assertStringContainsString('Heading', $html);
    }

    public function testAnAllowedTagIsKept(): void
    {
        $html = $this->purify('<h2>Heading</h2>', ['allowedHtmlTags' => ['h2']]);

        self::assertSame('<h2>Heading</h2>', $html);
    }

    public function testAnExternalLinkIsMarkedUp(): void
    {
        $html = $this->purify('<a href="https://example.test" target="_blank">Link</a>');

        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noreferrer noopener"', $html);
    }

    public function testAnUnknownSchemeIsRemoved(): void
    {
        $html = $this->purify('<a href="record:12">Link</a>');

        self::assertSame('<p><a>Link</a></p>', $html);
    }

    public function testARegisteredSchemeIsKeptBesideTheDefaultOnes(): void
    {
        $config = ['uriSchemes' => ['record' => new RecordUriScheme()]];

        $html = $this->purify('<a href="record:12#team">Ångström 東京 🚀</a> <a href="https://example.test">Link</a>', $config);

        self::assertSame('<p><a href="record:12#team">Ångström 東京 🚀</a> <a href="https://example.test" target="_blank" rel="noreferrer noopener">Link</a></p>', $html);
        self::assertSame('<p><a>Link</a></p>', $this->purify('<a href="record:abc">Link</a>', $config));
        self::assertSame('<p><a>Link</a></p>', $this->purify('<a href="other:12">Link</a>', $config));
    }

    public function testOnlyTheAllowedClassesSurvive(): void
    {
        $html = $this->purify('<a href="/x" class="btn evil">Link</a>', [
            'allowedClasses' => ['a' => ['Primary' => 'btn']],
        ]);

        self::assertStringContainsString('class="btn"', $html);
        self::assertStringNotContainsString('evil', $html);
    }

    public function testAClassOnATagThatDoesNotAllowOneIsRemoved(): void
    {
        $html = $this->purify('<p class="btn">Text</p>', [
            'allowedClasses' => ['a' => ['btn']],
        ]);

        self::assertSame('<p>Text</p>', $html);
    }

    public function testOnlyTheAllowedCssPropertiesSurvive(): void
    {
        $html = $this->purify('<p style="color:red;position:fixed">Text</p>', [
            'allowedHtmlAttributes' => ['p' => ['style']],
            'allowedCssProperties' => ['p' => ['color']],
        ]);

        // HtmlPurifier normalises a named colour to its hex value
        self::assertStringContainsString('color:#FF0000', $html);
        self::assertStringNotContainsString('position', $html);
    }

    public function testImagesAreOnlyKeptWhenTheyAreAllowed(): void
    {
        $img = '<p><img src="/test.jpg" alt="Test"></p>';

        self::assertStringNotContainsString('<img', $this->purify($img));

        $html = $this->purify($img, ['allowImages' => true]);

        self::assertStringContainsString('src="/test.jpg"', $html);
        self::assertStringContainsString('alt="Test"', $html);
    }

    public function testTablesAreOnlyKeptWhenTheyAreAllowed(): void
    {
        $table = '<table><tr><td>Cell</td></tr></table>';

        self::assertStringNotContainsString('<table>', $this->purify($table));
        self::assertStringContainsString('<td>Cell</td>', $this->purify($table, ['allowTables' => true]));
    }

    public function testEmptyParagraphsAreRemoved(): void
    {
        self::assertSame('', $this->purify('<p></p><p>  </p>'));
    }

    public function testTheEditorsEmptySpansAreRemoved(): void
    {
        $html = $this->purify('<p><span>Text</span></p>', ['removeUnnecessarySpanTags' => true]);

        self::assertSame('<p>Text</p>', $html);
        self::assertStringContainsString('<span>', $this->purify('<p><span>Text</span></p>'));
    }

    public function testWindowsLineBreaksAreUnified(): void
    {
        $html = $this->purify("First\r\nSecond");

        self::assertStringNotContainsString("\r", $html);
        self::assertStringContainsString('<br>', $html);
    }

    public function testAListSurvivesAutoParagraph(): void
    {
        $html = $this->purify("Intro\n<ul><li>One</li><li>Two</li></ul>");

        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>One</li>', $html);
        self::assertStringContainsString('<li>Two</li>', $html);
    }

    public function testAnEmptyValueStaysEmpty(): void
    {
        self::assertSame('', $this->purify(''));
        self::assertSame('', $this->purify(null));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function purify(?string $html, array $config = []): string
    {
        $translation = Translation::create();
        $translation->value = $html;

        (new HtmlValidator($config))->validateAttribute($translation, 'value');

        return (string)$translation->value;
    }
}

class RecordUriScheme extends HTMLPurifier_URIScheme
{
    /**
     * @var bool
     */
    public $browsable = false;

    /**
     * @var bool
     */
    public $may_omit_host = true;

    /**
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     */
    #[Override]
    public function doValidate(&$uri, $config, $context): bool
    {
        return $uri->host === null && $uri->query === null && preg_match('/^[1-9]\\d*$/', (string)$uri->path) === 1;
    }
}
