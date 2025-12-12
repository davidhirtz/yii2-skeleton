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
        $schema = new Schema([
            'db' => new Connection([
                'dsn' => 'mysql:host=localhost;dbname=test',
                'username' => 'user',
                'password' => 'pass',
            ]),
        ]);

        $command = $schema->getBackupCommand();
        $file = preg_match('/--defaults-file\'=\'([^\']+)/', $command, $matches) ? $matches[1] : null;

        self::assertNotNull($file);
        self::assertFileExists($file);

        $contents = file_get_contents($file);

        self::assertStringContainsString('[client]', $contents);
        self::assertStringContainsString("user=user", $contents);
        self::assertStringContainsString('password="pass"', $contents);
        self::assertStringContainsString('host=localhost', $contents);
        self::assertStringNotContainsString('port=', $contents);
    }
}
