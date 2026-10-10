<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Consent;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemNavItem;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class ConsentControllerTest extends TestCase
{
    use UserFixtureTrait;

    private const string UUID = '0b0e8a52-7c4f-4d1a-9b8e-3f6c2a1d5e7f';
    private const string OTHER_UUID = '9d4c3b2a-1e0f-4a5b-8c7d-6e5f4a3b2c1d';

    public function testIndexListsEveryDecision(): void
    {
        $this->login();

        $this->createConsent(self::UUID, ['required', 'analytics']);
        $this->createConsent(self::OTHER_UUID, ['required']);

        $html = Yii::$app->runAction('admin/consent/index');

        self::assertIsString($html);
        self::assertStringContainsString(self::UUID, $html);
        self::assertStringContainsString(self::OTHER_UUID, $html);
        self::assertStringContainsString('required, analytics', $html);
    }

    public function testIndexFiltersByConsentId(): void
    {
        $this->login();

        $this->createConsent(self::UUID, ['required']);
        $this->createConsent(self::OTHER_UUID, ['required']);

        $html = Yii::$app->runAction('admin/consent/index', ['q' => self::UUID]);

        self::assertIsString($html);
        self::assertStringContainsString(self::UUID, $html);
        self::assertStringNotContainsString(self::OTHER_UUID, $html);
    }

    public function testIndexIsForbiddenForANonAdmin(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/consent/index');
    }

    public function testIndexOfADisabledLogIsNotFound(): void
    {
        $this->login();
        $this->getAdminModule()->enableConsentLog = false;

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/consent/index');
    }

    public function testSystemNavigationOffersTheLogOnlyWhenEnabled(): void
    {
        $this->login();
        self::assertStringContainsString('admin/consent/index', (string)SystemNavItem::make());

        $this->getAdminModule()->enableConsentLog = false;
        self::assertStringNotContainsString('admin/consent/index', (string)SystemNavItem::make());
    }

    /**
     * @param list<string> $categories
     */
    private function createConsent(string $uuid, array $categories): void
    {
        $consent = Consent::create();
        $consent->uuid = $uuid;
        $consent->version = 'v1';
        $consent->categories = $categories;

        self::assertTrue($consent->insert(), print_r($consent->getErrors(), true));
    }

    private function getAdminModule(): Module
    {
        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);

        return $module;
    }

    private function login(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
