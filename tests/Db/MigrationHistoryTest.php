<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class MigrationHistoryTest extends TestCase
{
    public function testTheApplicationCollectsTheMigrationNamespacesOfEveryBundle(): void
    {
        $namespaces = Yii::$app->getMigrationNamespaces();

        self::assertContains('app\Migrations', $namespaces);
        self::assertContains('Hirtz\Skeleton\Migrations', $namespaces);
        self::assertSame($namespaces, array_unique($namespaces));
    }

    public function testSettingTheSameNamespaceTwiceRegistersItOnce(): void
    {
        $before = Yii::$app->getMigrationNamespaces();

        Yii::$app->setMigrationNamespace('Hirtz\Skeleton\Migrations');

        self::assertSame($before, Yii::$app->getMigrationNamespaces());
    }

    public function testTheTestDatabaseHasNoPendingMigrations(): void
    {
        self::assertSame([], $this->getHistory()->getPending());
    }

    public function testAMigrationMissingFromTheHistoryIsPending(): void
    {
        $history = $this->getHistory();
        $version = array_key_first($history->getApplied());

        self::assertIsString($version);

        Yii::$app->getDb()->createCommand()
            ->delete('{{%migration}}', ['version' => $version])
            ->execute();

        self::assertSame([$version], $this->getHistory()->getPending());
    }

    public function testTheLastAppliedMigrationIsTheNewestOne(): void
    {
        $history = $this->getHistory();
        $applied = $history->getApplied();
        $last = $history->getLastApplied();

        self::assertNotNull($last);

        if ($applied === []) {
            self::fail('No migration has been applied.');
        }

        self::assertSame(array_key_first($applied), $last['version']);
        self::assertSame(max($applied), $last['applyTime']);
    }

    public function testTheBaseMigrationIsNotReported(): void
    {
        self::assertArrayNotHasKey(MigrationHistory::BASE_MIGRATION, $this->getHistory()->getApplied());
    }

    private function getHistory(): MigrationHistory
    {
        return new MigrationHistory(Yii::$app->getDb());
    }
}
