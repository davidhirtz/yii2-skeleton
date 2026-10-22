<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountDeleteControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testDeletingTheOwnAccountKeepsItsFlash(): void
    {
        $this->getWebUser()->enableTwoFactorAuthentication = false;

        $user = $this->getUserFromFixture('admin');
        $this->getWebUser()->setIdentity($user);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = $this->getWebRequest();
        $request->setBodyParams(['value' => 'password', $request->csrfParam => $request->getCsrfToken()]);

        Yii::$app->runAction('admin/account/delete');

        self::assertNull(User::findOne($user->id));
        self::assertSame([Yii::t('skeleton', 'ACCOUNT_SUCCESS_DELETED')], $this->getWebSession()->getFlash('success'));
    }
}
