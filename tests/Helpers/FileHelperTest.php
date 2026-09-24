<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class FileHelperTest extends TestCase
{
    public function testGenerateRandomFilenameAndRename(): void
    {
        $folder = Yii::getAlias('@runtime/files');
        FileHelper::createDirectory($folder);

        $length = 20;
        $path = FileHelper::generateRandomFilename($folder, 'php', $length);

        self::assertStringEndsWith('.php', $path);
        self::assertEquals($length + 4, strlen(basename($path)));

        file_put_contents($path, '');

        $newPath = "$folder/renamed.php";
        FileHelper::rename($path, $newPath);
        self::assertFileExists($newPath);
        self::assertFileDoesNotExist($path);

        FileHelper::removeDirectory($folder);
        self::assertFileDoesNotExist($folder);
    }

    public function testAFilenameIsTakenByAnyOfItsExtensions(): void
    {
        $folder = Yii::getAlias('@runtime/files');
        FileHelper::createDirectory($folder);

        $basename = "$folder/photo";
        file_put_contents("$basename.png", '');

        self::assertTrue(FileHelper::isFilenameTaken($basename, ['jpg', 'png']));
        self::assertFalse(FileHelper::isFilenameTaken($basename, ['jpg']));
        self::assertFalse(FileHelper::isFilenameTaken($basename, []));
        self::assertFalse(FileHelper::isFilenameTaken("$folder/other", ['png']));

        FileHelper::removeDirectory($folder);
    }

    public function testUnlinkInvalidFile(): void
    {
        self::assertFalse(FileHelper::unlink('@runtime/invalid-file'));
    }

    public function testEncodeUrl(): void
    {
        $url = 'https://www.test.localhost/test file.txt';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('https://www.test.localhost/test%20file.txt', $encodedUrl);

        $url = '/üöä';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('/%C3%BC%C3%B6%C3%A4', $encodedUrl);
    }
}
