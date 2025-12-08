<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\db\mysql;

use Codeception\Test\Unit;
use Hirtz\Skeleton\db\Connection;
use Hirtz\Skeleton\db\mysql\Schema;

class SchemaTest extends Unit
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

        $this->assertNotNull($file);
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('[client]', $contents);
        $this->assertStringContainsString("user=user", $contents);
        $this->assertStringContainsString('password="pass"', $contents);
        $this->assertStringContainsString('host=localhost', $contents);
        $this->assertStringNotContainsString('port=', $contents);
    }
}
