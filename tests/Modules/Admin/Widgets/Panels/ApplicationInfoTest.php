<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\Dsn;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ApplicationInfo;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class ApplicationInfoTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheCardReportsTheApplicationEnvironmentAndPlatform(): void
    {
        $this->loginAdmin();
        $html = ApplicationInfo::make()->render();

        self::assertStringContainsString(Yii::$app->name, $html);
        self::assertStringContainsString('davidhirtz/yii2-monorepo', $html);
        self::assertStringContainsString(PHP_VERSION, $html);
        self::assertStringContainsString(Yii::getVersion(), $html);
    }

    public function testTheTestApplicationRunsInTheLocalEnvironment(): void
    {
        // `Test\TestCase` serves `www.test.localhost`
        $html = ApplicationInfo::make()->render();

        self::assertStringContainsString('Local', $html);
        self::assertStringContainsString('www.test.localhost', $html);
    }

    public function testTheDatabaseIsNamedWithItsDriverAndSchema(): void
    {
        $this->loginAdmin();

        $db = Yii::$app->getDb();
        $html = ApplicationInfo::make()->render();

        self::assertStringContainsString(
            Dsn::fromString($db->dsn)->database . ' · ' . $db->getDriverName(),
            $html,
        );
    }

    public function testTheLastAppliedMigrationIsReported(): void
    {
        $version = (string)Yii::$app->getDb()
            ->createCommand('SELECT version FROM {{%migration}} ORDER BY apply_time DESC, version DESC LIMIT 1')
            ->queryScalar();

        self::assertStringContainsString(
            htmlspecialchars($version, ENT_QUOTES),
            ApplicationInfo::make()->render(),
        );
    }

    public function testRowsAddedByAListenerAreAppended(): void
    {
        $html = ApplicationInfo::make()
            ->rows(fn (ApplicationInfo $info) => $info->addRow('Queue', 'redis'))
            ->render();

        self::assertStringContainsString('<div class="form-label">Queue</div>', $html);
    }

    public function testThePhpRowLinksToPhpInfoOutsideHtmx(): void
    {
        $this->loginAdmin();
        $html = ApplicationInfo::make()->render();

        self::assertStringContainsString('href="/admin/system/php-info"', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('hx-boost="false"', $html);
    }

    public function testTheExtensionsAreListedUnderTheYiiVersion(): void
    {
        $this->loginAdmin();
        $html = ApplicationInfo::make()->render();

        $yii = strpos($html, Yii::getVersion());
        $extensions = strpos($html, 'badge-list');

        self::assertIsInt($yii);
        self::assertIsInt($extensions);
        self::assertGreaterThan($yii, $extensions);
        self::assertStringContainsString('>yii2-skeleton</span>', $html);
    }

    public function testAManagerIsShownThePlatformWithoutItsInfrastructure(): void
    {
        $this->loginManager();

        $db = Yii::$app->getDb();
        $html = ApplicationInfo::make()->render();

        // the application, its commit and the PHP version stay
        self::assertStringContainsString(Yii::$app->name, $html);
        self::assertStringContainsString(PHP_VERSION, $html);
        self::assertStringContainsString(
            htmlspecialchars((string)VersionHelper::getApplicationReference(), ENT_QUOTES),
            $html,
        );

        // the repository, the schema, `phpinfo()` and the versions a maintainer reads do not
        self::assertStringNotContainsString('davidhirtz/yii2-monorepo', $html);
        self::assertStringNotContainsString(Dsn::fromString($db->dsn)->database, $html);
        self::assertStringNotContainsString('system/php-info', $html);
        self::assertStringNotContainsString('<div class="form-label">Yii</div>', $html);
        self::assertStringNotContainsString('badge-list', $html);
    }

    public function testTheSystemPermissionAloneRevealsTheInfrastructureFacts(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignManagerRole($user->id);
        $this->assignPermission($user->id, Module::AUTH_SYSTEM);

        Yii::$app->getUser()->setIdentity($user);
        $html = ApplicationInfo::make()->render();

        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_ROLE_ADMIN));
        self::assertStringContainsString('davidhirtz/yii2-monorepo', $html);
        self::assertStringContainsString('system/php-info', $html);
        self::assertStringContainsString(Yii::getVersion(), $html);
    }

    private function loginAdmin(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        Yii::$app->getUser()->setIdentity($user);
    }

    private function loginManager(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignManagerRole($user->id);

        Yii::$app->getUser()->setIdentity($user);
    }
}
