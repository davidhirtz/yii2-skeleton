<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ServerInfo;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class ServerInfoTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheCardNamesTheServerMailerAndTimeZone(): void
    {
        $this->loginAdmin();
        $html = ServerInfo::make()->render();

        self::assertStringContainsString('<div class="form-label">Server</div>', $html);
        self::assertStringContainsString('<div class="form-label">Mailer</div>', $html);
        self::assertStringContainsString(Yii::$app->timeZone, $html);
        self::assertStringContainsString((string)php_uname('n'), $html);
    }

    public function testTheHostNameIsHiddenFromAManager(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignManagerRole($user->id);
        $this->getWebUser()->setIdentity($user);

        $html = ServerInfo::make()->render();

        self::assertStringContainsString('<div class="form-label">Server</div>', $html);
        self::assertStringNotContainsString((string)php_uname('n'), $html);
    }

    public function testTheDirectoriesAreLeftToTheAlert(): void
    {
        $html = ServerInfo::make()->render();

        self::assertStringNotContainsString('@runtime', $html);
        self::assertStringNotContainsString('badge', $html);
    }

    public function testTheMailerDsnCredentialsAreNeverRendered(): void
    {
        Yii::$app->params['mailerDsn'] = 'smtp://postmaster:hunter2@mail.example.com:587';
        Yii::$app->getMailer()->useFileTransport = false;

        $html = ServerInfo::make()->render();

        self::assertStringContainsString('smtp://mail.example.com:587', $html);
        self::assertStringNotContainsString('hunter2', $html);
        self::assertStringNotContainsString('postmaster', $html);
    }

    public function testTheFileTransportIsNamedWithItsPath(): void
    {
        Yii::$app->getMailer()->useFileTransport = true;

        $html = ServerInfo::make()->render();

        self::assertStringContainsString('File transport', $html);
        self::assertStringContainsString(Yii::$app->getMailer()->fileTransportPath, $html);
    }

    public function testAProxiedRequestWithoutTrustedHostsIsFlagged(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
        $this->getWebRequest()->trustedHosts = [];

        $html = ServerInfo::make()->render();

        self::assertStringContainsString('class="badge badge-warning"', $html);
        self::assertStringContainsString('Not configured', $html);
        self::assertStringContainsString('X-Forwarded-For', $html);
    }

    public function testAProxiedRequestWithTrustedHostsIsNotFlagged(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
        $this->getWebRequest()->trustedHosts = ['10.0.0.0/8'];

        $html = ServerInfo::make()->render();

        self::assertStringNotContainsString('badge-warning', $html);
        self::assertStringContainsString('10.0.0.0/8', $html);
    }

    public function testAnUnproxiedRequestIsNotFlagged(): void
    {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->getWebRequest()->trustedHosts = [];

        $html = ServerInfo::make()->render();

        self::assertStringNotContainsString('badge-warning', $html);
        self::assertStringContainsString('<div class="form-label">Trusted hosts</div>', $html);
    }

    private function loginAdmin(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
