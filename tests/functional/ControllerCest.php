<?php

/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;

class ControllerCest extends \davidhirtz\yii2\skeleton\codeception\BaseCest
{
    public function checkApplicationHealth(FunctionalTester $I): void
    {
        $I->amOnPage('/health');
        $I->seeResponseCodeIsSuccessful();
    }

    public function checkSitemapXML(FunctionalTester $I): void
    {
        $I->amOnPage('/sitemap.xml');
        $I->seeResponseCodeIsSuccessful();
        $I->haveHttpHeader('Content-Type', 'text/html; charset=UTF-8"');
    }
}
