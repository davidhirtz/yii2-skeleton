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
        $upload = $this->getStreamUploadedFile(['url' => static::VALID_URL]);

        $this->assertEquals('README.md', $upload->name);
        $this->assertEquals('text/plain', $upload->type);
        $this->assertEquals(UPLOAD_ERR_OK, $upload->error);

        $this->assertTrue($upload->saveAs('@runtime/README.md'));

        $path = Yii::getAlias('@runtime/README.md');
        $this->assertFileExists($path);
        @unlink($path);
    }

    public function testErrorsForInvalidFiles(): void
    {
        $upload = $this->getStreamUploadedFile();
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getStreamUploadedFile(['url' => 'invalid-file']);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
        $this->assertFalse($upload->saveAs('@runtime/invalid-file'));

        $upload = $this->getStreamUploadedFile([
            'url' => static::VALID_URL,
            'allowedExtensions' => ['jpg', 'png'],
        ]);

        $this->assertEquals(UPLOAD_ERR_EXTENSION, $upload->error);
    }

    public function testUploadFromUnencodedUrl(): void
    {
        $upload = $this->getStreamUploadedFile([
            'url' => 'https://raw.githubusercontent.com/davidhirtz/yii2-skeleton/master/tests/support/files/Ümlauts & Spaces.md',
            'allowedExtensions' => ['md', 'txt'],
        ]);

        $this->assertEquals(UPLOAD_ERR_OK, $upload->error);
    }

    public function testInvalidTempDirectory()
    {
        $upload = $this->getStreamUploadedFile([
            'url' => static::VALID_URL,
            'tempName' => '/invalid/temp/path',
        ]);

        $this->assertEquals(UPLOAD_ERR_CANT_WRITE, $upload->error);
    }

    protected function getStreamUploadedFile(array $config = []): StreamUploadedFile
    {
        return Yii::$container->get(StreamUploadedFile::class, [], $config);
    }
}
