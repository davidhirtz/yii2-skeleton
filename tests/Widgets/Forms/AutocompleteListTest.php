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

    public function testAnEmptyListStillCarriesTheIdSoTheSwapClearsThePopover(): void
    {
        $html = AutocompleteList::make()->render();

        self::assertStringContainsString('id="' . AutocompleteField::OPTIONS_ID . '"', $html);
        self::assertStringContainsString('No results found.', $html);
        self::assertStringNotContainsString('data-autocomplete-value', $html);
    }
}
