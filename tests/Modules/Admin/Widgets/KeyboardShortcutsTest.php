<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\KeyboardShortcuts;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class KeyboardShortcutsTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheListNamesTheLetterShortcutsOfAUserWhoUsesThem(): void
    {
        $this->login();

        $html = KeyboardShortcuts::make()->render();

        self::assertStringContainsString('<template id="keyboard-shortcuts" data-letters="1"', $html);
        self::assertStringContainsString('<kbd data-modifier>Ctrl</kbd> <kbd>S</kbd>', $html);
        self::assertStringContainsString('<kbd>M</kbd>', $html);
        self::assertStringContainsString('<kbd>?</kbd>', $html);
    }

    public function testTheAccountSettingTurnsTheLettersOffButNotTheModifiers(): void
    {
        $this->login()->keyboard_shortcuts = false;

        $html = KeyboardShortcuts::make()->render();

        self::assertStringContainsString('data-letters="0"', $html);
        self::assertStringContainsString('<kbd data-modifier>Ctrl</kbd> <kbd>K</kbd>', $html);
        self::assertStringNotContainsString('<kbd>M</kbd>', $html);
        self::assertStringNotContainsString('<kbd>/</kbd>', $html);
    }

    public function testAGuestGetsNoList(): void
    {
        self::assertSame('', KeyboardShortcuts::make()->render());
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
