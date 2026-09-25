<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db\Mysql;

use Hirtz\Skeleton\Db\Connection;
use Hirtz\Skeleton\Db\Mysql\Schema;
use Hirtz\Skeleton\Test\TestCase;

class SchemaTest extends TestCase
{
    public function testGetBackupCommand(): void
    {
        $schema = new Schema(['db' => self::createConnection()]);

        $command = $schema->getBackupCommand();
        $file = preg_match('/--defaults-file\'=\'([^\']+)/', $command, $matches) ? $matches[1] : null;

        self::assertNotNull($file);
        self::assertFileExists($file);

        $contents = file_get_contents($file);
        self::assertNotFalse($contents);

        self::assertStringContainsString('[client]', $contents);
        self::assertStringContainsString("user=user", $contents);
        self::assertStringContainsString('password="pass"', $contents);
        self::assertStringContainsString('host=localhost', $contents);
        self::assertStringNotContainsString('port=', $contents);
    }

    /**
     * Raw bytes such as `user_login.ip_address` are not UTF-8, and an importer reading the dump as text refuses it.
     */
    public function testGetBackupCommandDumpsBinaryColumnsAsHex(): void
    {
        $schema = new Schema(['db' => self::createConnection()]);

        self::assertSame(2, substr_count($schema->getBackupCommand(), "'--hex-blob'"));
    }

    /**
     * Without the ceiling a table another connection still holds a shared metadata lock on blocks the dump's
     * `DROP TABLE` for `lock_wait_timeout`, a day on MariaDB (monorepo #141).
     */
    public function testGetRestoreCommandCarriesALockWaitTimeout(): void
    {
        $schema = new Schema(['db' => self::createConnection()]);

        self::assertStringContainsString("'--init-command'='SET SESSION lock_wait_timeout=60'", $schema->getRestoreCommand());
    }

    public function testGetRestoreCommandWithoutLockWaitTimeout(): void
    {
        $schema = new Schema(['db' => self::createConnection(['lockWaitTimeout' => 0])]);

        self::assertStringNotContainsString('--init-command', $schema->getRestoreCommand());
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function createConnection(array $config = []): Connection
    {
        return new Connection([
            'dsn' => 'mysql:host=localhost;dbname=test',
            'username' => 'user',
            'password' => 'pass',
            ...$config,
        ]);
    }
}
