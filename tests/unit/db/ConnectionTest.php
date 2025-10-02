<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\db;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\helpers\Console;

class ConnectionTest extends Unit
{
    public function testBackup(): void
    {
        $db = Yii::$app->getDb();
        $db->maxBackups = 1;

        $filePath = $db->backup();
        Console::stdout(PHP_EOL . " > Filepath: $filePath" . PHP_EOL);

        self::assertFileExists($filePath);
        self::assertStringContainsString('runtime/backups/yii2_skeleton_test', $filePath);
        self::assertStringEndsWith('.sql', $filePath);

        $newFilePath = $db->backup();
        Console::stdout(PHP_EOL . "New Filepath: $newFilePath" . PHP_EOL);

        self::assertNotSame($filePath, $newFilePath);
        self::assertFileExists($newFilePath);
        self::assertStringEndsWith('-1.sql', $newFilePath);

        self::assertFileNotExists($filePath);

        FileHelper::removeDirectory($db->backupPath);
    }
}
