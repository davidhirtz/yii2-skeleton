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

    /**
     * A history naming a class that cannot be loaded is how a database that predates the v3 namespace rename
     * is recognised. `Console\Controllers\MigrateController` refuses to run against one, because Yii would
     * treat every migration as new and build the schema again over populated tables.
     */
    public function testAnAppliedMigrationWhoseClassIsGoneIsUnresolved(): void
    {
        $db = Yii::$app->getDb();
        $history = new MigrationHistory($db);

        self::assertSame([], $history->getUnresolved());

        $db->createCommand()->insert('{{%migration}}', [
            'version' => 'davidhirtz\yii2\skeleton\migrations\M190125140002Init',
            'apply_time' => 1_700_000_000,
        ])->execute();

        $history->refresh();

        self::assertSame(['davidhirtz\yii2\skeleton\migrations\M190125140002Init'], $history->getUnresolved());
    }

    /**
     * `m000000_000000_base` carries no namespace and is not a class, so it must never be reported.
     */
    public function testTheBaseMigrationIsNeverUnresolved(): void
    {
        $history = new MigrationHistory(Yii::$app->getDb());

        self::assertNotContains(MigrationHistory::BASE_MIGRATION, $history->getUnresolved());
    }
}
