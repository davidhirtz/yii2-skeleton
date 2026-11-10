<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;

class LinkFieldTest extends TestCase
{
    public function testWithoutSuggestionsItIsAPlainInput(): void
    {
        $html = LinkField::make()
            ->attribute('name', 'link')
            ->attribute('value', '/kontakt')
            ->render();

        self::assertStringNotContainsString('data-link-field', $html);
        self::assertStringContainsString('name="link" value="/kontakt"', $html);
    }

    public function testATypedUrlShowsTheInputWithSuggestions(): void
    {
        $html = LinkField::make()
            ->attribute('name', 'link')
            ->attribute('value', 'https://www.example.com/')
            ->url('/suggest')
            ->render();

        self::assertStringContainsString('data-link-field', $html);
        self::assertStringContainsString('hx-get="/suggest"', $html);
        self::assertMatchesRegularExpression('/class="link-field-selected input-group" hidden/', $html);
        self::assertStringContainsString('<div class="link-field-input">', $html);
    }

    /**
     * The input stays in the form, hidden, so the link keeps posting.
     */
    public function testAPickShowsItsNameWithTheInputHidden(): void
    {
        $html = LinkField::make()
            ->attribute('name', 'link')
            ->attribute('value', 'entry:12')
            ->selected('Ågot 東京 🚀', '/karriere', '/admin/entry?id=12')
            ->render();

        self::assertStringContainsString('<div class="link-field-input" hidden>', $html);
        self::assertStringContainsString('name="link" value="entry:12"', $html);
        self::assertStringContainsString('>Ågot 東京 🚀</span>', $html);
        self::assertStringContainsString('>/karriere</span>', $html);
        self::assertStringContainsString('href="/admin/entry?id=12"', $html);
        self::assertStringContainsString('data-link-field-clear', $html);
    }

    public function testASuggestionUrlNamedInPrepareStillRequestsThem(): void
    {
        $html = LinkField::make()
            ->attribute('name', 'link')
            ->prepare(fn (LinkField $field) => $field->url('/suggest'))
            ->render();

        self::assertStringContainsString('hx-get="/suggest"', $html);
        self::assertStringContainsString('data-autocomplete-search', $html);
    }
}
