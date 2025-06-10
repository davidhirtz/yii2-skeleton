<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use Yii;

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
