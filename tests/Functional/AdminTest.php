<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

class AdminTest extends TestCase
{
    use FunctionalTestTrait;

    public function testLoginRedirect(): void
    {
        $this->open('https://draft.example.com/admin');

        self::assertCurrentUrlEquals('https://www.example.com/admin/account/login');
        self::assertAnyAlertErrorSame('You must login to view this page!');
    }
}
