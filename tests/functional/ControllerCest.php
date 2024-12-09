<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
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

    public function checkDraftSubdomain(FunctionalTester $I): void
    {
        $I->setDraftSubdomain();
        $I->amOnPage('/application-health');

        $I->assertTrue(Yii::$app->request->getIsDraft());

        $I->setProductionSubdomain();
        $I->amOnPage('/application-health');

        $I->assertFalse(Yii::$app->request->getIsDraft());
    }
}
