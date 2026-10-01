<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Panels;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Panels\Card;

/**
 * The state of a collapsible card is announced by its buttons, which control its body (monorepo issue #410).
 */
class CardTest extends TestCase
{
    public function testTheToggleButtonsCarryTheState(): void
    {
        $html = (string)Card::make()
            ->attribute('id', 'card')
            ->title('Title')
            ->collapsed(true)
            ->content('Content')
            ->render();

        self::assertSame(2, substr_count($html, 'aria-controls="card-body" aria-expanded="false" data-collapse="#card"'));
        self::assertStringContainsString('<div id="card-body" class="card-body">', $html);
        self::assertStringNotContainsString('<div id="card" class="collapsed card" aria-expanded', $html);

        $html = (string)Card::make()
            ->attribute('id', 'card')
            ->title('Title')
            ->collapsed(false)
            ->render();

        self::assertSame(2, substr_count($html, 'aria-expanded="true"'));
    }

    public function testACardThatDoesNotCollapseHasNoToggle(): void
    {
        $html = (string)Card::make()
            ->title('Title')
            ->render();

        self::assertStringNotContainsString('aria-expanded', $html);
        self::assertStringNotContainsString('aria-controls', $html);
    }
}
