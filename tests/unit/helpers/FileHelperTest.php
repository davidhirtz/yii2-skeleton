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
        $this->assertStringEndsWith('.php', $filename);
        $this->assertEquals(20 + 4, strlen($filename));

        $config = [
            'string' => 'this is a string',
            'integer' => 123,
            'float' => 123.456,
        ];

        $result = FileHelper::createConfigFile("$folder/$filename", $config, 'Test config file');
        $this->assertIsInt($result);

        $path = Yii::getAlias("$folder/$filename");
        $this->assertFileExists($path);

        $loadedConfig = require($path);
        $this->assertEquals($config, $loadedConfig);

        $extension = FileHelper::getExtensionFromUrl($filename);
        $this->assertEquals('php', $extension);

        $newPath = "$folder/renamed.$extension";
        FileHelper::rename($path, $newPath);
        $this->assertFileExists($newPath);

        FileHelper::removeDirectory($folder);
        $this->assertFileDoesNotExist($folder);
    }

    public function testUnlinkInvalidFile(): void
    {
        $this->assertFalse(FileHelper::unlink('@runtime/invalid-file'));
    }
}