<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\AdminModelHeading;

class AdminModelHeadingTest extends TestCase
{
    use UserFixtureTrait;

    public function testARecordOwningItsPageIsItsNameAlone(): void
    {
        $user = $this->getUserFromFixture('admin');

        $html = AdminModelHeading::make()
            ->model($user)
            ->render();

        self::assertSame('<span class="model-heading"><span class="model-heading-title">' . $user->getAdminName() . '</span></span>', $html);
    }

    public function testNoRecordIsNothing(): void
    {
        self::assertSame('', AdminModelHeading::make()->render());
    }
}
