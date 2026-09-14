<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\Dsn;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ApplicationInfo;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ApplicationInfoTest extends TestCase
{
    public function testTheCardReportsTheApplicationEnvironmentAndPlatform(): void
    {
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

        self::assertStringContainsString('<th scope="row">Queue</th>', $html);
    }
}
