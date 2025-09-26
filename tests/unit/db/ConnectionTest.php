<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\db;

use Codeception\Test\Unit;
use Yii;

class ConnectionTest extends Unit
{
    public function testBackup(): void
    {
        $db = Yii::$app->getDb();
        $db->maxBackups = 1;

        $filePath = $db->backup();

        self::assertFileExists($filePath);
        self::assertStringContainsString('runtime/backups/yii2_skeleton_test', $filePath);
        self::assertStringEndsWith('.sql', $filePath);

        $newFilePath = $db->backup();

        self::assertNotSame($filePath, $newFilePath);
        self::assertFileExists($newFilePath);
        self::assertStringEndsWith('-1.sql', $newFilePath);

        self::assertFileNotExists($filePath);

        unlink($newFilePath);
    }
}
