<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\MigrateController;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Override;
use Yii;

class MigrateControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    private string $configPath = '@runtime/config';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        FileHelper::createDirectory($this->configPath);
    }

    #[\Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->configPath);
        parent::tearDown();
    }

    public function testActionUp(): void
    {
        $controller = $this->createMigrationController();
        $controller->runAction('up');

        self::assertStringContainsString('No new migrations found. Your system is up-to-date.', $controller->flushStdOutBuffer());
    }

    public function testActionUpWithoutDsn(): void
    {
        $controller = $this->createMigrationController();
        $controller->resetDbCredentials();

        $controller->interactive = false;
        $controller->runAction('up');

        self::assertEquals('Database connection not configured.' . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testActionBackupAndRestore(): void
    {
        $controller = $this->createMigrationController();
        $controller->runAction('backup');

        $stdout = $controller->flushStdOutBuffer();
        self::assertStringContainsString('Backing up database ... done', $stdout);

        $backups = Application::current()->getDb()->getBackups();
        self::assertNotEmpty($backups);

        $filename = $backups[0];

        // The restore drops and recreates every table through the `mysql` client, which needs an exclusive
        // metadata lock on each of them — and the test transaction holds a shared one on every table it has
        // read, `migration` among them since `beforeAction()` reads the history. The two wait on each other
        // for `lock_wait_timeout`, a day by default, so the run wedges rather than fails. `tearDown()` notices
        // the ended transaction and unloads the fixtures by hand.
        Application::current()->getDb()->getTransaction()?->rollBack();

        $controller->runAction('restore');
        $stdout = $controller->flushStdOutBuffer();

        self::assertStringContainsString(basename((string) $filename), $stdout);
        self::assertStringContainsString('Restoring database from backup ... done', $stdout);

        unlink($filename);
    }

    public function testActionConfig(): void
    {
        $controller = $this->createMigrationController();
        $controller->dbFile = "$this->configPath/db.php";
        $controller->runAction('config');

        self::assertStringContainsString('Generate database connection credentials?', $controller->flushStdOutBuffer());
    }

    public function testActionConfigWithCredentials(): void
    {
        $controller = $this->createMigrationController();
        $controller->resetDbCredentials();

        $controller->dbFile = "$this->configPath/db.php";
        $controller->confirmCreateDbCredentialsPrompt = true;
        $controller->runAction('up');

        $stdout = $controller->flushStdOutBuffer();

        self::assertStringContainsString('Database connection credentials saved.', $stdout);
        self::assertStringContainsString('No new migrations found. Your system is up-to-date.', $stdout);

        self::assertFileExists(Yii::getAlias($controller->dbFile));
    }

    protected function createMigrationController(): MigrateControllerMock
    {
        return new MigrateControllerMock('migration', Application::current());
    }
}

class MigrateControllerMock extends MigrateController
{
    use StdOutBufferControllerTrait;

    public bool $confirmCreateDbCredentialsPrompt = false;

    private ?string $dbHost = null;
    private ?string $dbName = null;
    private ?string $dbPort = null;
    private ?string $dbUsername = null;
    private ?string $dbPassword = null;

    public function resetDbCredentials(): void
    {
        $dsn = (string)Application::current()->getDb()->dsn;

        $this->dbHost = preg_replace('/^.*host=([^;]+).*$/i', '$1', $dsn);
        $this->dbName = preg_replace('/^.*dbname=([^;]+).*$/i', '$1', $dsn);
        $this->dbPort = preg_replace('/^.*port=([^;]+).*$/i', '$1', $dsn);

        $this->dbUsername = Application::current()->getDb()->username;
        $this->dbPassword = Application::current()->getDb()->password;

        Application::current()->getDb()->dsn = '';
    }

    #[Override]
    public function confirm($message, $default = false): bool
    {
        switch ($message) {
            case 'Override existing database connection credentials?':
            case 'Generate database connection credentials?':
                if ($this->confirmCreateDbCredentialsPrompt) {
                    return true;
                }
                break;

            case 'Create owner user account?':
                return false;
        }

        $this->stdout($message);
        return false;
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function prompt($text, $options = []): string
    {
        return match ($text) {
            'Enter database host:' => $this->dbHost,
            'Enter port or leave empty:' => $this->dbPort,
            'Enter database name:' => $this->dbName,
            'Enter username:' => $this->dbUsername,
            'Select backup to restore (number):' => '1',
            default => parent::prompt($text, $options),
        };
    }

    #[Override]
    public function hiddenPasswordPrompt(): string
    {
        return $this->dbPassword;
    }
}
