<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Flashes;
use Yii;

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

    public function testFlashesAreAnnouncedAndFailuresInterrupt(): void
    {
        $this->getWebSession()->setFlash('success', 'Saved.');
        $this->getWebSession()->setFlash('danger', 'Failed.');

        $html = (string)Flashes::make();

        self::assertStringContainsString('<div id="flashes" class="flashes hidden-empty" aria-live="polite">', $html);
        self::assertStringContainsString('<div class="alert" data-alert="success">', $html);
        self::assertStringContainsString('<div class="alert" role="alert" data-alert="danger"><div class="icon"><span class="fas fa-exclamation-circle"></span></div><div class="alert-content">Failed.', $html);
    }

    /**
     * Rendered empty and beside the container, which stays empty until a script clones it in.
     */
    public function testTheNetworkErrorWaitsInATemplate(): void
    {
        $html = (string)Flashes::make();

        self::assertStringContainsString('<div id="flashes" class="flashes hidden-empty" aria-live="polite"></div><template id="network-error-flash">', $html);
        self::assertStringContainsString(Yii::t('skeleton', 'COMMON_NETWORK_ERROR'), $html);
        self::assertStringContainsString('data-alert="danger"', $html);
    }

    public function testRepeatedMessagesCollapseIntoOneAlert(): void
    {
        foreach (range(1, 50) as $i) {
            $this->getWebSession()->addFlash('success', 'The file was created.');
        }

        $html = (string)Flashes::make();

        self::assertSame(1, substr_count(strstr($html, '<template', true) ?: $html, '<flash-alert>'));
        self::assertStringContainsString('The file was created. <span class="alert-count">50</span>', $html);
    }

    public function testMessagesBeyondTheLimitFoldIntoTheLastAlert(): void
    {
        foreach (['Å', 'ą', 'Ж', '漢', '🎉', "Mu\u{308}ller", '404'] as $name) {
            $this->getWebSession()->addFlash('success', "The asset “{$name}” was created.");
        }

        $this->getWebSession()->addFlash('danger', 'Failed.');

        $html = (string)Flashes::make()->limit(3);

        self::assertSame(4, substr_count(strstr($html, '<template', true) ?: $html, '<flash-alert>'));
        self::assertStringContainsString('<div class="alert-content">The asset “ą” was created.</div>', $html);
        self::assertStringContainsString('<details><summary>' . Yii::t('skeleton', 'FLASHES_MORE', ['count' => 5]) . '</summary><ul><li>The asset “Ж” was created.</li>', $html);
        self::assertStringContainsString('<li>The asset “404” was created.</li></ul></details>', $html);
        self::assertStringContainsString('Failed.', $html);
    }
}
