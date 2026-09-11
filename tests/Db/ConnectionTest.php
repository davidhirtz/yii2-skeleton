<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\Dsn;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ConnectionTest extends TestCase
{
    public function testBackup(): void
    {
        $db = Yii::$app->getDb();

        $db->backupPath = Yii::getAlias("$this->webroot/backups");
        $db->maxBackups = 1;

        $filePath = $db->backup();

        $database = Dsn::fromString($db->dsn)->database;
        $date = date('Y-m-d');
        $expected = "$db->backupPath/$database-$date.sql";

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
