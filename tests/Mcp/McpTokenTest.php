<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class McpTokenTest extends TestCase
{
    use UserFixtureTrait;

    public function testAnUnconfirmedUsersTokenFollowsTheLoginPolicy(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['email_confirmed_at' => null]);

        $mcp = Mcp::getComponent();
        $token = $mcp->issueToken($user, false);

        self::assertNotNull($mcp->findToken($token));

        $this->getWebUser()->enableUnconfirmedEmailLogin = false;
        self::assertNull($mcp->findToken($token));
    }
}
