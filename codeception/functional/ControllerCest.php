<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\functional;

use Hirtz\Skeleton\Codeception\functional\BaseCest;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Tests\support\FunctionalTester;
use Yii;

class ControllerCest extends BaseCest
{
    public function checkApplicationHealth(FunctionalTester $I): void
    {
        $I->amOnPage('/application-health');
        $I->seeResponseCodeIsSuccessful();
        $I->assertFalse(Yii::$app->request->getIsDraft());
    }

    public function checkSitemapXML(FunctionalTester $I): void
    {
        $I->amOnPage('/sitemap.xml');
        $I->seeResponseCodeIsSuccessful();
        $I->haveHttpHeader('Content-Type', 'text/html; charset=UTF-8"');
    }

    public function checkAdminRedirect(FunctionalTester $I): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');

        $I->amOnPage("/$module->alias");
        $I->canSeeCurrentUrlEquals("/$module->alias/account/login");
    }
}
