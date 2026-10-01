<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Username;

class UsernameTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * Only `User::$namePattern` keeps markup out of a name, and a project may switch it off.
     */
    public function testANameWithoutALinkIsEscaped(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['name' => '<b hx-get=/x>p</b>']);

        $html = (string)Username::make()->user($user);

        self::assertSame('<span>&lt;b hx-get=/x&gt;p&lt;/b&gt;</span>', $html);
    }

    public function testANameWithALinkIsEscaped(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['name' => '<b hx-get=/x>p</b>']);

        $html = (string)Username::make()->user($user)->href('/x');

        self::assertStringContainsString('>&lt;b hx-get=/x&gt;p&lt;/b&gt;</a>', $html);
    }
}
