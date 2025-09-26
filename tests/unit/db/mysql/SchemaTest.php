<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\db\mysql;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\db\mysql\Schema;
use yii\db\Connection;

class SchemaTest extends Unit
{
    public function testCreatedDumpConfigFile(): void
    {
        $schema = new class() extends Schema {
            public function getCreatedDumpConfigFile(): string
            {
                return $this->createDumpConfigFile();
            }
        };

        $schema->db = new Connection([
            'dsn' => 'mysql:host=localhost;dbname=test',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $file = $schema->getCreatedDumpConfigFile();
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('[client]', $contents);
        $this->assertStringContainsString("user=user", $contents);
        $this->assertStringContainsString('password="pass"', $contents);
        $this->assertStringContainsString('host=localhost', $contents);
        $this->assertStringNotContainsString('port=', $contents);

        unlink($file);
    }
}
