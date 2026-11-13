<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Yii;

class PrivacyLinkTest extends TestCase
{
    use FunctionalTestTrait;

    public function testNoticeWithoutUrl(): void
    {
        $this->open('admin/account/login');

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.privacy', 'Each login is saved with its IP address and browser.');
        self::assertSelectorNotExists('.privacy a');
    }

    public function testLoginLinksPrivacyPolicy(): void
    {
        $this->getAdminModule()->privacyUrl = 'https://www.example.com/privacy';
        $this->open('admin/account/login');

        self::assertSelectorExists('.privacy a[href="https://www.example.com/privacy"][target="_blank"]');
        self::assertSelectorTextContains('.privacy', 'privacy policy');
    }

    public function testSignupLinksPrivacyPolicy(): void
    {
        $this->getWebUser()->enableSignup = true;
        $this->getAdminModule()->privacyUrl = ['/site/privacy'];
        $this->open('admin/account/create');

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('.privacy a[href="/site/privacy"]');
    }

    private function getAdminModule(): Module
    {
        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(Module::class, $module);

        return $module;
    }
}
