<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemNavItem;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class SystemNavItemTest extends TestCase
{
    use UserFixtureTrait;

    public function testItIsHiddenFromAUserWithoutASystemPage(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        self::assertSame('', SystemNavItem::make()->render());
    }

    public function testItLeadsToTheRedirectsOfAUserWhoMayOnlyOpenThose(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Redirect::AUTH_REDIRECT);
        $this->getWebUser()->setIdentity($user);

        self::assertStringStartsWith('<li class="nav-item"><a class="nav-link" href="/admin/redirect/index">', SystemNavItem::make()->render());
    }

    public function testItLeadsToTheSystemPageOfAManager(): void
    {
        $user = $this->getUserFromFixture('admin');
        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getRole(User::AUTH_ROLE_MANAGER) ?? self::fail('No manager role.'), $user->id);
        $this->getWebUser()->setIdentity($user);

        self::assertStringStartsWith('<li class="nav-item"><a class="nav-link" href="/admin/system/index">', SystemNavItem::make()->render());
    }
}
