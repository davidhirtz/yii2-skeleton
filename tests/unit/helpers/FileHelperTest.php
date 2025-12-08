<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\helpers;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Helpers\FileHelper;
use Yii;

class FileHelperTest extends Unit
{
    public function testConfigFile(): void
    {
        $folder = Yii::getAlias('@runtime/file-helper');
        $length = 20;
        $filename = FileHelper::generateRandomFilename($folder, 'php', $length);

        self::assertStringEndsWith('.php', $filename);
        self::assertEquals($length + 4, strlen(basename($filename)));

        $config = [
            'string' => 'this is a string',
            'integer' => 123,
            'float' => 123.456,
        ];

        $result = FileHelper::createConfigFile("$folder/$filename", $config, 'Test config file');
        self::assertIsInt($result);

        $path = Yii::getAlias("$folder/$filename");
        self::assertFileExists($path);

        $loadedConfig = require($path);
        self::assertEquals($config, $loadedConfig);

        $extension = FileHelper::getExtensionFromUrl($filename);
        self::assertEquals('php', $extension);

        $newPath = "$folder/renamed.$extension";
        FileHelper::rename($path, $newPath);
        self::assertFileExists($newPath);

        FileHelper::removeDirectory($folder);
        self::assertFileDoesNotExist($folder);
    }

    public function testUnlinkInvalidFile(): void
    {
        self::assertFalse(FileHelper::unlink('@runtime/invalid-file'));
    }

    public function testEncodeUrl(): void
    {
        $url = 'https://www.example.com/test file.txt';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('https://www.example.com/test%20file.txt', $encodedUrl);

        $url = '/üöä';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('/%C3%BC%C3%B6%C3%A4', $encodedUrl);
    }
}
