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
        self::assertStringContainsString('hx-select-oob="unset"', $html);
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
}
