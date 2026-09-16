<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fields\HexColorField;

class HexColorFieldTest extends TestCase
{
    /**
     * The two inputs are kept in sync by the `color-picker` element alone, so the field is broken without it.
     */
    public function testTheInputsAreWrappedInTheColorPickerElement(): void
    {
        $content = HexColorField::make()
            ->attribute('value', '#ff0000')
            ->render();

        self::assertStringContainsString('<color-picker><div class="input-group">', $content);
        self::assertStringContainsString('<input type="color" id="i1-color" class="input" value="#ff0000" required>', $content);
        self::assertStringContainsString('</div></color-picker>', $content);
    }

    public function testAShorthandValueIsExpandedForTheColorInput(): void
    {
        $content = HexColorField::make()
            ->attribute('value', 'f00')
            ->render();

        self::assertStringContainsString('<input type="color" id="i1-color" class="input" value="#ff0000" required>', $content);
        self::assertStringContainsString('value="#f00"', $content);
    }
}
