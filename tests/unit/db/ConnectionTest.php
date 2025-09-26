<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\db;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\db\Connection;
use davidhirtz\yii2\skeleton\helpers\FileHelper;

class ConnectionTest extends Unit
{
    public function testGetBackupFilePath(): void
    {
        $connection = new Connection([
            'dsn' => 'mysql:host=127.0.0.1;dbname=yii2_skeleton_test',
            'backupPath' => '@runtime/backups',
        ]);

        $filePath = $connection->getBackupFilePath();

        self::assertStringContainsString('runtime/backups/yii2_skeleton_test', $filePath);
        self::assertStringEndsWith('.sql', $filePath);

        FileHelper::createDirectory($connection->backupPath);
        file_put_contents($filePath, 'test');

        $filePath2 = $connection->getBackupFilePath();
        self::assertNotSame($filePath, $filePath2);

        self::assertStringContainsString('runtime/backups/yii2_skeleton_test', $filePath2);
        self::assertStringEndsWith('-1.sql', $filePath2);

        FileHelper::removeDirectory($connection->backupPath);
    }
}
