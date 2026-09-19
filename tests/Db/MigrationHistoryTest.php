<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\MigrationHistory;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class MigrationHistoryTest extends TestCase
{
    /** A backslash is what makes `getUnresolved()` look at a version at all. */
    private const string UNDECLARABLE = 'Gone\\M240101000000Undeclarable';

    /** @var (callable(): void)|null */
    private $undeclarable = null;

    #[Override]
    protected function tearDown(): void
    {
        ($this->undeclarable ?? static fn () => null)();
        $this->undeclarable = null;

        parent::tearDown();
    }

    public function testTheApplicationCollectsTheMigrationNamespacesOfEveryBundle(): void
    {
        $namespaces = Yii::$app->getMigrationNamespaces();

        self::assertContains('App\Migrations', $namespaces);
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
     * `class_exists()` returns false for a class that is simply absent and **throws** for one whose file is
     * found and cannot be declared. That is the shape of a project migration still using a trait v3 removed,
     * so the throw would come from inside the guard that exists to report it — and `./yii migrate` would die
     * with a stack trace on the upgrade deploy, with maintenance mode left on.
     */
    public function testAnAppliedMigrationWhoseClassCannotBeDeclaredIsUnresolvedRatherThanFatal(): void
    {
        $db = Yii::$app->getDb();
        $history = new MigrationHistory($db);

        $db->createCommand()->insert('{{%migration}}', [
            'version' => $this->registerUndeclarableClass(),
            'apply_time' => 1_700_000_000,
        ])->execute();

        $history->refresh();

        self::assertSame([self::UNDECLARABLE], $history->getUnresolved());
    }

    /**
     * A class whose file is on disk and whose trait is not. Autoloaded from `@runtime` rather than committed,
     * so no fixture of this repository is a file PHP cannot declare.
     */
    private function registerUndeclarableClass(): string
    {
        $file = Yii::getAlias('@runtime/undeclarable-migration.php');

        file_put_contents($file, sprintf(
            '<?php namespace %s; final class %s { use \Gone\MissingTrait; }',
            substr(self::UNDECLARABLE, 0, (int)strrpos(self::UNDECLARABLE, '\\')),
            substr(strrchr(self::UNDECLARABLE, '\\') ?: '', 1),
        ));

        $loader = static function (string $name) use ($file): void {
            if ($name === self::UNDECLARABLE) {
                require $file;
            }
        };

        spl_autoload_register($loader);
        $this->undeclarable = static function () use ($loader, $file): void {
            spl_autoload_unregister($loader);
            is_file($file) && unlink($file);
        };

        return self::UNDECLARABLE;
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
