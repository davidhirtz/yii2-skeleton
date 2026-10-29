<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\MigrateController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Override;
use Yii;

class MigrateControllerHistoryTest extends TestCase
{
    protected string $applicationClass = Application::class;

    private const string LEGACY = 'davidhirtz\yii2\skeleton\migrations\M190125140002Init';
    private const string UNDECLARABLE = 'Gone\M240101000000Undeclarable';

    /** @var (callable(): void)|null */
    private $undeclarable = null;

    #[Override]
    protected function tearDown(): void
    {
        ($this->undeclarable ?? static fn () => null)();
        $this->undeclarable = null;

        // `migration` carries no fixture, so a row inserted here outlives the transaction of a test that ran
        // DDL and would be seen by the next one.
        Yii::$app->getDb()->createCommand()
            ->delete('{{%migration}}', ['like', 'version', 'davidhirtz\\yii2\\'])
            ->execute();

        parent::tearDown();
    }

    /**
     * `backup` stays unguarded, since `repairHistory()` calls it, while reading a missing history takes a metadata
     * lock.
     */
    public function testOnlyTheActionsThatApplyMigrationsAreGuarded(): void
    {
        $this->insertLegacyRow();

        $controller = $this->createController();
        $controller->upgradeFile = '@runtime/does-not-exist.php';

        self::assertTrue($controller->checkHistoryPublic('backup'));
        self::assertTrue($controller->checkHistoryPublic('create'));
        self::assertTrue($controller->checkHistoryPublic('history'));
        self::assertFalse($controller->checkHistoryPublic('up'));
    }

    public function testAResolvableHistoryIsLeftAlone(): void
    {
        $controller = $this->createController();

        self::assertTrue($controller->checkHistoryPublic());
        self::assertSame('', $controller->flushStdOutBuffer());
    }

    public function testItRefusesWhenTheProjectShipsNoRepair(): void
    {
        $this->insertLegacyRow();

        $controller = $this->createController();
        $controller->upgradeFile = '@runtime/does-not-exist.php';

        self::assertFalse($controller->checkHistoryPublic());
        self::assertStringContainsString(self::LEGACY, $controller->flushStdOutBuffer());
    }

    /**
     * The file's presence is the authorisation, since a deployment cannot be asked to confirm anything.
     */
    public function testItRunsTheRepairTheProjectShipsAndContinues(): void
    {
        $this->insertLegacyRow(commit: true);

        $file = Yii::getAlias('@runtime/repair.php');
        // Stands in for the generated `upgrade/collapse.php`: a standalone script on a raw connection that
        // boots no application, which is the whole point of the real one.
        file_put_contents($file, sprintf(
            '<?php require %s; require %s;'
            . ' $db = new yii\db\Connection(%s); $db->open();'
            . ' $db->createCommand()->delete("{{%%migration}}", ["version" => %s])->execute();',
            var_export(Yii::getAlias('@vendor/autoload.php'), true),
            var_export(Yii::getAlias('@vendor/yiisoft/yii2/Yii.php'), true),
            var_export(['dsn' => Yii::$app->getDb()->dsn, 'username' => Yii::$app->getDb()->username,
                'password' => Yii::$app->getDb()->password], true),
            var_export(self::LEGACY, true),
        ));

        $controller = $this->createController();
        $controller->upgradeFile = '@runtime/repair.php';
        $controller->skipBackup = true;

        self::assertTrue($controller->checkHistoryPublic());

        unlink($file);
    }

    /**
     * The run must not continue, since Yii would next treat every migration as new.
     */
    public function testARepairThatChangesNothingStillRefuses(): void
    {
        $this->insertLegacyRow();

        $file = Yii::getAlias('@runtime/noop.php');
        file_put_contents($file, '<?php // does nothing');

        $controller = $this->createController();
        $controller->upgradeFile = '@runtime/noop.php';
        $controller->skipBackup = true;

        self::assertFalse($controller->checkHistoryPublic());

        unlink($file);
    }

    /**
     * A v2 migration still `use`ing a removed trait makes `class_exists()` throw, killing `./yii migrate` in
     * maintenance mode.
     *
     * @see https://github.com/davidhirtz/yii2-monorepo/issues/199
     */
    public function testAMigrationWhoseClassCannotBeDeclaredIsReportedRatherThanFatal(): void
    {
        $class = $this->registerUndeclarableClass();

        Yii::$app->getDb()->createCommand()
            ->insert('{{%migration}}', ['version' => $class, 'apply_time' => 1_700_000_000])
            ->execute();

        $controller = $this->createController();
        $controller->upgradeFile = '@runtime/does-not-exist.php';

        self::assertFalse($controller->checkHistoryPublic());
        self::assertStringContainsString($class, $controller->flushStdOutBuffer());
    }

    /**
     * A missing parent rather than trait, since a missing trait was a compile-time fatal before PHP 8.5.
     */
    private function registerUndeclarableClass(): string
    {
        $file = Yii::getAlias('@runtime/undeclarable-migration.php');

        file_put_contents($file, sprintf(
            '<?php namespace %s; final class %s extends \Gone\MissingParent {}',
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
     * The repair runs on a connection of its own, so the row is committed and `tearDown()` cleans up.
     */
    private function insertLegacyRow(bool $commit = false): void
    {
        $db = Yii::$app->getDb();

        $db->createCommand()
            ->insert('{{%migration}}', ['version' => self::LEGACY, 'apply_time' => 1_700_000_000])
            ->execute();

        if ($commit) {
            $db->getTransaction()?->commit();
        }
    }

    private function createController(): MigrateHistoryControllerMock
    {
        return new MigrateHistoryControllerMock('migration', Application::current());
    }
}

class MigrateHistoryControllerMock extends MigrateController
{
    use StdOutBufferControllerTrait;

    public function checkHistoryPublic(string $actionId = 'up'): bool
    {
        return $this->checkHistory($actionId);
    }
}
