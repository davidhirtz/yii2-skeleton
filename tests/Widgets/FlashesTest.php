<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Flashes;

class FlashesTest extends TestCase
{
    public function testTheContainerCarriesNoOutOfBandSwap(): void
    {
        $this->getWebSession()->setFlash('success', 'Saved.');

        $html = (string)Flashes::make();

        self::assertStringContainsString('id="flashes"', $html);
        self::assertStringContainsString('Saved.', $html);

        // The container lives in the layout, so an `hx-swap-oob` on it is restored into the page with every history
        // snapshot and then consumed out of it, which leaves the next response's alerts without a target.
        self::assertStringNotContainsString('hx-swap-oob', $html);
    }
}
