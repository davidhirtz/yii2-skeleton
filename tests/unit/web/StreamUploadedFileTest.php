<?php

namespace davidhirtz\yii2\skeleton\tests\unit\web;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;

class StreamUploadedFileTest extends Unit
{
    protected const VALID_URL = 'https://raw.githubusercontent.com/davidhirtz/yii2-skeleton/master/README.md';

    public function _after(): void
    {
        FileHelper::removeDirectory(Yii::getAlias('@runtime/uploads'));
    }

    public function testSaveValidFile(): void
    {
        $streamUploadedFile = $this->getStreamUploadedFile(['url' => static::VALID_URL]);

        $this->assertEquals('README.md', $streamUploadedFile->name);
        $this->assertEquals('text/plain', $streamUploadedFile->type);
        $this->assertEquals(UPLOAD_ERR_OK, $streamUploadedFile->error);

        $this->assertTrue($streamUploadedFile->saveAs('@runtime/README.md'));

        $this->assertTrue($streamUploadedFile->saveAs('@runtime/README.md'));

        $path = Yii::getAlias('@runtime/README.md');
        $this->assertFileExists($path);
        @unlink($path);
    }

    public function testErrorsForInvalidFiles(): void
    {
        $streamUploadedFile = $this->getStreamUploadedFile();
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $streamUploadedFile->error);

        $streamUploadedFile = $this->getStreamUploadedFile(['url' => 'invalid-file']);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $streamUploadedFile->error);
        $this->assertFalse($streamUploadedFile->saveAs('@runtime/invalid-file'));

        $streamUploadedFile = $this->getStreamUploadedFile([
            'url' => static::VALID_URL,
            'allowedExtensions' => ['jpg', 'png'],
        ]);

        $this->assertEquals(UPLOAD_ERR_EXTENSION, $streamUploadedFile->error);
    }

    protected function getStreamUploadedFile(array $config = []): StreamUploadedFile
    {
        return Yii::$container->get(StreamUploadedFile::class, [], $config);
    }
}