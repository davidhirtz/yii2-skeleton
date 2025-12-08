<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\functional;

use Hirtz\Skeleton\codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\codeception\functional\BaseCest;
use Hirtz\Skeleton\modules\admin\Module;
use Hirtz\Skeleton\tests\support\FunctionalTester;
use Yii;

/**
 * @noinspection PhpUnused
 */

class LogoutCest extends BaseCest
{
    use UserFixtureTrait;

    public function checkLogoutFromDashboard(FunctionalTester $I): void
    {
        $user = $I->grabUserFixture('admin');
        $this->assignAdminRole($user->id);

        $I->amLoggedInAs($user);

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $I->amOnPage("/$module->alias");
        $I->seeElement('.navbar-logout');

        $I->sendAjaxPostRequest("/$module->alias/account/logout", [
            '_csrf' => Yii::$app->getRequest()->getCsrfToken(),
        ]);

        $I->assertTrue(Yii::$app->getUser()->getIsGuest());
    }
}
