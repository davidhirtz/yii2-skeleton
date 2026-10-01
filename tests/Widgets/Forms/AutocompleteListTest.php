<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\AutocompleteList;
use Hirtz\Skeleton\Widgets\Forms\Fields\AutocompleteField;

class AutocompleteListTest extends TestCase
{
    public function testTheOptionsCarryTheirValueAndTheEncodedText(): void
    {
        $html = AutocompleteList::make()
            ->options([
                ['text' => 'Zürich & Winterthur', 'value' => 'ChIJ1'],
                ['text' => 'Bern', 'value' => 'ChIJ2'],
            ])
            ->render();

        self::assertStringContainsString('id="' . AutocompleteField::OPTIONS_ID . '"', $html);
        self::assertStringContainsString('data-autocomplete-value="ChIJ1"', $html);
        self::assertStringContainsString('Zürich &amp; Winterthur', $html);
        self::assertStringContainsString('data-autocomplete-value="ChIJ2"', $html);
    }

    /**
     * The field's popover is the listbox, so the list and its items step aside, and the options stay out of the tab
     * order: the focus stays in the input.
     */
    public function testTheOptionsAreTheListboxOptions(): void
    {
        $html = AutocompleteList::make()
            ->options([['text' => 'Bern', 'value' => 'ChIJ2']])
            ->render();

        self::assertMatchesRegularExpression('/<ul[^>]*role="none"/', $html);
        self::assertMatchesRegularExpression('/<li[^>]*role="none"/', $html);
        self::assertMatchesRegularExpression('/<button[^>]*role="option"[^>]*tabindex="-1"/', $html);
    }

    public function testAnEmptyListStillCarriesTheIdSoTheSwapClearsThePopover(): void
    {
        $html = AutocompleteList::make()->render();

        self::assertStringContainsString('id="' . AutocompleteField::OPTIONS_ID . '"', $html);
        self::assertStringContainsString('No results found.', $html);
        self::assertMatchesRegularExpression('/<li[^>]*role="option"[^>]*aria-disabled="true"/', $html);
        self::assertStringNotContainsString('data-autocomplete-value', $html);
    }
}
