<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class HintAlertTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheHintIsShownToAUserWhoAsksForThem(): void
    {
        $this->login('owner');

        $alert = HintAlert::make()->text('A block belongs to no page.');
        $html = $alert->render();

        self::assertTrue($alert->isVisible());
        self::assertStringContainsString('data-alert="info"', $html);
        self::assertStringContainsString('A block belongs to no page.', $html);
    }

    public function testTheAccountSettingSilencesEveryHint(): void
    {
        $user = $this->login('owner');
        $user->show_hints = false;

        self::assertSame('', HintAlert::make()->text('A block belongs to no page.')->render());
    }

    public function testAGuestIsShownNoHint(): void
    {
        self::assertSame('', HintAlert::make()->text('A block belongs to no page.')->render());
    }

    public function testAHintWithoutATextRendersNothing(): void
    {
        $this->login('owner');

        self::assertSame('', HintAlert::make()->render());
    }

    private function login(string $key): User
    {
        $user = $this->getUserFromFixture($key);
        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
