<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\AutocompleteField;

class AutocompleteFieldTest extends TestCase
{
    public function testAFieldWithoutAUrlIsAPlainInput(): void
    {
        $html = AutocompleteField::make()->render();

        self::assertStringContainsString('autocomplete="off"', $html);
        self::assertStringNotContainsString('data-autocomplete', $html);
        self::assertStringNotContainsString('popover', $html);
    }

    public function testTheFieldOverridesTheInheritedHtmxAttributes(): void
    {
        $html = AutocompleteField::make()
            ->url(['/admin/location/location/autocomplete'])
            ->render();

        self::assertStringContainsString('hx-get="/admin/location/location/autocomplete"', $html);
        self::assertStringContainsString('hx-select="#' . AutocompleteField::OPTIONS_ID . '"', $html);
        self::assertStringContainsString('hx-select-oob=""', $html);
        self::assertStringContainsString('hx-swap="innerHTML"', $html);
    }

    /**
     * The popover is targeted by id, so the input has to name the container rendered beside it.
     */
    public function testTheInputTargetsItsOwnResultsPopover(): void
    {
        $html = AutocompleteField::make()
            ->url('/autocomplete')
            ->render();

        self::assertMatchesRegularExpression('/hx-target="#(i\d+)"[^>]*>.*<div id="\1"[^>]*popover/s', $html);
        self::assertStringContainsString('data-autocomplete-results', $html);
    }

    public function testTheInputIsAComboboxControllingItsListbox(): void
    {
        $html = AutocompleteField::make()
            ->url('/autocomplete')
            ->label('Place')
            ->render();

        self::assertMatchesRegularExpression('/<input[^>]*role="combobox"/', $html);
        self::assertMatchesRegularExpression('/<input[^>]*aria-autocomplete="list"/', $html);
        self::assertMatchesRegularExpression('/<input[^>]*aria-expanded="false"/', $html);
        self::assertMatchesRegularExpression('/aria-controls="(i\d+)"[^>]*>.*<div id="\1"[^>]*role="listbox"[^>]*aria-label="Place"/s', $html);
    }

    public function testAPlainInputIsNoCombobox(): void
    {
        self::assertStringNotContainsString('combobox', AutocompleteField::make()->render());
    }

    /**
     * Derived from the input id, so a group's row template rewrites it with the name rather than repeat it per row.
     */
    public function testTheResultsIdIsDerivedFromTheInputId(): void
    {
        $html = AutocompleteField::make()
            ->url('/autocomplete')
            ->attribute('id', 'section-gallery-__ID__-image')
            ->render();

        self::assertStringContainsString('hx-target="#section-gallery-__ID__-image-results"', $html);
        self::assertStringContainsString('<div id="section-gallery-__ID__-image-results"', $html);
    }

    public function testTheHiddenModePostsTheValueAndShowsTheText(): void
    {
        $html = AutocompleteField::make()
            ->url('/autocomplete?scope=abc')
            ->hidden()
            ->text('Москва & 東京.jpg')
            ->attribute('name', 'Section[hero]')
            ->attribute('id', 'section-hero')
            ->value('12')
            ->render();

        self::assertStringContainsString('<input type="hidden" name="Section[hero]" value="12" data-autocomplete-value-input>', $html);
        self::assertMatchesRegularExpression('/<input type="text" id="section-hero"[^>]*value="Москва &amp; 東京.jpg"[^>]*data-autocomplete-search/u', $html);
        self::assertSame(1, substr_count($html, 'name="Section[hero]"'));
    }
}
