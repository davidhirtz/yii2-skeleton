<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\ContentUploadedFile;
use Override;

class ContentUploadedFileTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory(Upload::getComponent()->tempPath);
        parent::tearDown();
    }

    public function testTheContentIsWrittenUnderTheBasename(): void
    {
        $file = new ContentUploadedFile(['content' => 'Grüße – Привет 🎉', 'filename' => '..\\..\\etc/Notiz 東京.txt']);

        self::assertEquals(UPLOAD_ERR_OK, $file->error);
        self::assertSame('Notiz 東京.txt', $file->name);
        self::assertSame('Grüße – Привет 🎉', file_get_contents($file->tempName));
        self::assertSame(strlen('Grüße – Привет 🎉'), $file->size);
    }

    public function testEmptyContentIsNoFile(): void
    {
        self::assertSame(UPLOAD_ERR_NO_FILE, (new ContentUploadedFile(['content' => '', 'filename' => 'a.txt']))->error);
    }

    public function testContentBeyondTheLimitIsRefused(): void
    {
        Upload::getComponent()->maxStreamUploadSize = 4;
        $file = new ContentUploadedFile(['content' => 'zu lang', 'filename' => 'a.txt']);

        self::assertSame(UPLOAD_ERR_INI_SIZE, $file->error);
        self::assertFileDoesNotExist($file->tempName);
    }
}
