<?php

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\ConsoleApplicationTrait;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

class MigrationControllerTest extends Unit
{
    use ConsoleApplicationTrait;

    protected string $configPath = '@runtime/config';

    protected function _before(): void
    {
        $this->createConsoleApplicationMock();
        FileHelper::createDirectory($this->configPath);

        parent::_before();
    }

    protected function _after(): void
    {
        FileHelper::removeDirectory($this->configPath);
        parent::_after();
    }

    public function testActionUp(): void
    {
        $controller = $this->createMigrationController();

        /**  @covers MigrateController::actionUp() */
        $controller->runAction('up');

        $this->assertStringContainsString('No new migrations found. Your system is up-to-date.', $controller->flushStdOutBuffer());
    }

    public function testActionUpWithoutDsn(): void
    {
        $controller = $this->createMigrationController();
        $controller->resetDbCredentials();

        $controller->interactive = false;

        /**  @covers MigrateController::actionConfig() */
        $controller->runAction('up');

        $this->assertEquals('Database connection not configured.', $controller->flushStdOutBuffer());
    }

    public function testActionConfig(): void
    {
        $controller = $this->createMigrationController();
        $controller->dbFile = "$this->configPath/db.php";

        /**  @covers MigrateController::actionConfig() */
        $controller->runAction('config');

        $this->assertStringContainsString('Generate database connection credentials?', $controller->flushStdOutBuffer());
    }

    public function testActionConfigWithCredentials(): void
    {
        $controller = $this->createMigrationController();
        $controller->resetDbCredentials();

        $controller->dbFile = "$this->configPath/db.php";
        $controller->autoConfirm = true;

        /**  @covers MigrateController::actionUp() */
        $controller->runAction('up');

        $stdout = $controller->flushStdOutBuffer();

        $this->assertStringContainsString('Database connection credentials saved.', $stdout);
        $this->assertStringContainsString('No new migrations found. Your system is up-to-date.', $stdout);

        $this->assertFileExists(Yii::getAlias($controller->dbFile));
    }

    protected function createMigrationController(): MigrationControllerMock
    {
        return new MigrationControllerMock('migration', Yii::$app);
    }
}

class MigrationControllerMock extends MigrateController
{
    use StdOutBufferControllerTrait;

    public bool $autoConfirm = false;

    private ?string $dbUsername = null;
    private ?string $dbPassword = null;
    private ?string $dbName = null;

    public function resetDbCredentials(): void
    {
        $this->dbUsername = Yii::$app->getDb()->username;
        $this->dbPassword = Yii::$app->getDb()->password;

        $dbname = preg_replace('/^.*dbname=([^;]+).*$/i', '$1', (string) Yii::$app->getDb()->dsn);
        $this->dbName = $dbname;

        Yii::$app->getDb()->dsn = '';
    }

    public function confirm($message, $default = false): bool
    {
        if ($this->autoConfirm) {
            return true;
        }

        $this->stdout($message);
        return false;
    }

    public function prompt($text, $options = []): string
    {
        // grab dbname= from dsn
        $dbname = preg_replace('/^.*dbname=([^;]+).*$/i', '$1', (string) Yii::$app->getDb()->dsn);
        codecept_debug("dbname: $dbname");

        return match ($text) {
            'Enter database host:' => 'localhost',
            'Enter port or leave empty:' => '',
            'Enter database name:' => $this->dbName,
            'Enter username:' => $this->dbUsername,
            default => parent::prompt($text, $options),
        };
    }

    public function hiddenPasswordPrompt(): string
    {
        return $this->dbPassword;
    }
}
