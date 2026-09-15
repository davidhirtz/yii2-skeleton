<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\CopiedUploadedFile;
use Override;
use Yii;

class CopiedUploadedFileTest extends TestCase
{
    protected const string CONTENT = "# Skeleton\n";

    private string $source = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->source = (string)Yii::getAlias('@runtime/copied-uploaded-file/README.md');
        FileHelper::createDirectory(dirname($this->source));
        file_put_contents($this->source, self::CONTENT);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory(dirname($this->source));
        FileHelper::removeDirectory(Upload::getComponent()->tempPath);

        parent::tearDown();
    }

    public function testCopyValidFile(): void
    {
        $upload = $this->getCopiedUploadedFile(['path' => $this->source]);

        self::assertEquals('README.md', $upload->name);
        self::assertEquals('text/plain', $upload->type);
        self::assertEquals(UPLOAD_ERR_OK, $upload->error);
        self::assertEquals(strlen(self::CONTENT), $upload->size);

        self::assertTrue($upload->saveAs('@runtime/copied-uploaded-file/copy.md'));
        self::assertFileExists(Yii::getAlias('@runtime/copied-uploaded-file/copy.md'));
    }

    public function testErrorsForInvalidFiles(): void
    {
        $upload = $this->getCopiedUploadedFile();
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);

        $upload = $this->getCopiedUploadedFile(['path' => dirname($this->source) . '/missing.md']);
        self::assertEquals(UPLOAD_ERR_NO_FILE, $upload->error);
        self::assertFalse($upload->saveAs('@runtime/copied-uploaded-file/missing.md'));

        $upload = $this->getCopiedUploadedFile([
            'path' => $this->source,
            'allowedExtensions' => ['jpg', 'png'],
        ]);

        self::assertEquals(UPLOAD_ERR_EXTENSION, $upload->error);
    }

    public function testInvalidTempDirectory(): void
    {
        $upload = $this->getCopiedUploadedFile([
            'path' => $this->source,
            'tempName' => '/invalid/temp/path',
        ]);

        self::assertEquals(UPLOAD_ERR_CANT_WRITE, $upload->error);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function getCopiedUploadedFile(array $config = []): CopiedUploadedFile
    {
        return Yii::$container->get(CopiedUploadedFile::class, [], $config);
    }
}
