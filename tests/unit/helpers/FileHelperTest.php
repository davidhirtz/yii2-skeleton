<?php

namespace davidhirtz\yii2\skeleton\tests\unit\helpers;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

class FileHelperTest extends Unit
{
    public function testConfigFile(): void
    {
        $folder = Yii::getAlias('@runtime/file-helper');

        $filename = FileHelper::generateRandomFilename('php', 20);
        self::assertStringEndsWith('.php', $filename);
        self::assertEquals(20 + 4, strlen($filename));

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

    public function testEncodeUrl()
    {
        $url = 'https://www.example.com/test file.txt';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('https://www.example.com/test%20file.txt', $encodedUrl);

        $url = '/üöä';
        $encodedUrl = FileHelper::encodeUrl($url);
        self::assertEquals('/%C3%BC%C3%B6%C3%A4', $encodedUrl);
    }
}
