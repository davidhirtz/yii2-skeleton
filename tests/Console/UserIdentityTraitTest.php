<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\Traits\UserIdentityTrait;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use yii\console\Controller;

class UserIdentityTraitTest extends TestCase
{
    use UserFixtureTrait;

    protected string $applicationClass = Application::class;

    public function testTheOwnerIsTheDefault(): void
    {
        $controller = $this->createController();

        self::assertTrue($controller->setUserIdentity());

        $owner = $this->getUserFromFixture('owner');
        $redirect = $this->insertRedirect();

        self::assertSame($owner->id, WebUser::current()?->getId());
        self::assertSame($owner->id, $redirect->updated_by_user_id);
        self::assertSame($owner->id, Trail::find()
            ->where(['model_class' => Redirect::class, 'model_id' => (string)$redirect->id])
            ->one()
            ?->user_id);
    }

    public function testTheUserOptionPicksTheUser(): void
    {
        $controller = $this->createController();
        $controller->user = 'F2A@domain.com';

        self::assertTrue($controller->setUserIdentity());
        self::assertSame($this->getUserFromFixture('admin')->id, $this->insertRedirect()->updated_by_user_id);
    }

    public function testAnUnknownUserIsRefused(): void
    {
        $controller = $this->createController();
        $controller->user = 'nobody@domain.com';

        self::assertFalse($controller->setUserIdentity());
        self::assertStringContainsString('No user found for nobody@domain.com', $controller->flushStdOutBuffer());
        self::assertNull(WebUser::current());
    }

    private function createController(): UserIdentityControllerMock
    {
        return new UserIdentityControllerMock('user-identity', Application::current());
    }

    private function insertRedirect(): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'old';
        $redirect->url = 'new';
        $redirect->insert();

        return $redirect;
    }
}

/**
 * @extends Controller<\yii\base\Module>
 */
class UserIdentityControllerMock extends Controller
{
    use StdOutBufferControllerTrait;
    use UserIdentityTrait {
        setUserIdentity as public;
    }
}
