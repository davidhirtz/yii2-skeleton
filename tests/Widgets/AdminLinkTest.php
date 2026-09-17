<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\AdminLink;
use Yii;
use yii\base\Model;

class AdminLinkTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheRecordsAdminRouteIsLinked(): void
    {
        $user = $this->login(User::AUTH_USER);
        $html = AdminLink::tag($user);

        self::assertStringContainsString(Yii::$app->getUrlManager()->createUrl($user->getAdminRoute()), $html);
        self::assertStringContainsString('class="admin"', $html);
        self::assertStringContainsString('target="_blank"', $html);
    }

    public function testAGuestIsLinkedNowhere(): void
    {
        self::assertSame('', AdminLink::tag($this->getUserFromFixture('admin')));
    }

    public function testAnAccountWithoutThePermissionIsLinkedNowhere(): void
    {
        self::assertSame('', AdminLink::tag($this->login(User::AUTH_USER_ASSIGN)));
    }

    /**
     * A model with no admin page of its own is linked nowhere however the permission answers, which is what keeps
     * the widget usable for a record type that only sometimes has one.
     */
    public function testARecordWithoutAnAdminRouteIsLinkedNowhere(): void
    {
        $this->login(User::AUTH_USER);

        $model = new class () extends Model implements AdminModelInterface {
            use AdminModelTrait;

            public function getAdminRoute(): array|false
            {
                return false;
            }

            public function getPermissionName(): string
            {
                return User::AUTH_USER;
            }
        };

        self::assertSame('', AdminLink::tag($model));
    }

    /**
     * The caller's attributes replace the defaults, so an overlay that is styled by the project keeps saying so.
     */
    public function testTheDefaultAttributesGiveWay(): void
    {
        $user = $this->login(User::AUTH_USER);

        $html = AdminLink::make()
            ->model($user)
            ->class('admin edit')
            ->render();

        self::assertStringContainsString('class="admin edit"', $html);
    }

    private function login(string $permission): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, $permission);
        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
