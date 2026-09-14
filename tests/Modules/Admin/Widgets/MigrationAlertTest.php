<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Modules\Admin\Widgets\MigrationAlert;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class MigrationAlertTest extends TestCase
{
    public function testTheAlertIsInvisibleWhileEveryMigrationIsApplied(): void
    {
        $alert = MigrationAlert::make();

        self::assertSame('', $alert->render());
        self::assertFalse($alert->isVisible());
        self::assertSame([], $alert->getPending());
    }

    public function testTheAlertNamesTheNumberOfPendingMigrations(): void
    {
        $html = MigrationAlert::make()
            ->pending(['Hirtz\Skeleton\Migrations\M260101000000Foo', 'Hirtz\Cms\Migrations\M260101000001Bar'])
            ->render();

        self::assertStringContainsString('data-alert="danger"', $html);
        self::assertStringContainsString('2 migrations have not been applied', $html);
    }

    public function testASinglePendingMigrationIsReportedInTheSingular(): void
    {
        $html = MigrationAlert::make()
            ->pending(['Hirtz\Skeleton\Migrations\M260101000000Foo'])
            ->render();

        self::assertStringContainsString('1 migration has not been applied', $html);
    }

    public function testAMigrationMissingFromTheHistoryRaisesTheAlert(): void
    {
        $db = Yii::$app->getDb();
        $version = (string)$db->createCommand('SELECT version FROM {{%migration}} LIMIT 1')->queryScalar();

        $db->createCommand()
            ->delete('{{%migration}}', ['version' => $version])
            ->execute();

        $alert = MigrationAlert::make();

        self::assertStringContainsString('1 migration has not been applied', $alert->render());
        self::assertSame([$version], $alert->getPending());
    }
}
