<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ConnectionTest extends TestCase
{
    public function testBackup(): void
    {
        $db = Yii::$app->getDb();
        $db->maxBackups = 1;

        $filePath = $db->backup();
        $expected = Yii::getAlias('@runtime/backups/') . 'yii2_test-' . date('Y-m-d') . '.sql';

        self::assertFileExists($filePath);
        self::assertEquals($expected, $filePath);
        self::assertStringEndsWith('.sql', $filePath);

        $newFilePath = $db->backup();

        self::assertNotSame($filePath, $newFilePath);
        self::assertFileExists($newFilePath);
        self::assertStringEndsWith('-1.sql', $newFilePath);

        self::assertFileDoesNotExist($filePath);

        FileHelper::removeDirectory($db->backupPath);
    }
}
