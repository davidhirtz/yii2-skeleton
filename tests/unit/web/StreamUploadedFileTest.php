<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\web;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\web\StreamUploadedFile;
use Yii;

class StreamUploadedFileTest extends Unit
{
    protected const string VALID_URL = 'https://raw.githubusercontent.com/davidhirtz/yii2-skeleton/master/README.md';

    public function _after(): void
    {
        FileHelper::removeDirectory(Yii::getAlias('@runtime/uploads'));
    }

    public function testSaveValidFile(): void
    {
        $upload = $this->getStreamUploadedFile(['url' => static::VALID_URL]);

        self::assertEquals('README.md', $upload->name);
        self::assertEquals('text/plain', $upload->type);
        self::assertEquals(UPLOAD_ERR_OK, $upload->error);

        self::assertTrue($upload->saveAs('@runtime/README.md'));

        $path = Yii::getAlias('@runtime/README.md');
        self::assertFileExists($path);
        @unlink($path);
    }

    public function testErrorsForInvalidFiles(): void
    {
        $upload = $this->getStreamUploadedFile();
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getStreamUploadedFile(['url' => 'invalid-file']);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
        self::assertFalse($upload->saveAs('@runtime/invalid-file'));

        $upload = $this->getStreamUploadedFile([
            'url' => static::VALID_URL,
            'allowedExtensions' => ['jpg', 'png'],
        ]);

        self::assertEquals(UPLOAD_ERR_EXTENSION, $upload->error);
    }

    public function testUploadFromUnencodedUrl(): void
    {
        $upload = $this->getStreamUploadedFile([
            'url' => 'https://raw.githubusercontent.com/davidhirtz/yii2-skeleton/master/tests/support/files/Ümlauts & Spaces.md',
            'allowedExtensions' => ['md', 'txt'],
        ]);

        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
    }

    public function testInvalidTempDirectory()
    {
        $upload = $this->getStreamUploadedFile([
            'url' => static::VALID_URL,
            'tempName' => '/invalid/temp/path',
        ]);

        self::assertEquals(UPLOAD_ERR_CANT_WRITE, $upload->error);
    }

    protected function getStreamUploadedFile(array $config = []): StreamUploadedFile
    {
        return Yii::$container->get(StreamUploadedFile::class, [], $config);
    }
}
