<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\ChunkedUploadedFile;
use Override;
use Yii;
use yii\base\InvalidCallException;

class ChunkedUploadedFileTest extends TestCase
{
    private string $path;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->path = Yii::getAlias('@runtime/test-uploads') . '/';
        FileHelper::createDirectory($this->path);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->path);
        parent::tearDown();
    }

    public function testAnUploadWithoutAContentRangeIsLeftAlone(): void
    {
        $source = $this->createSourceFile('abc');
        $file = $this->createUploadedFile($source, size: 3);

        self::assertSame($source, $file->tempName);
        self::assertSame(UPLOAD_ERR_OK, $file->error);
    }

    public function testAMalformedContentRangeIsLeftAlone(): void
    {
        $source = $this->createSourceFile('abc');
        $file = $this->createUploadedFile($source, size: 3, range: 'bytes */3');

        self::assertSame($source, $file->tempName);
    }

    public function testTheChunksAreAppendedInOrder(): void
    {
        $first = $this->createUploadedFile($this->createSourceFile('abc', 'a'), range: 'bytes 0-2/9');

        self::assertSame(UPLOAD_ERR_PARTIAL, $first->error);
        self::assertTrue($first->isPartial());
        self::assertFalse($first->isCompleted());

        $second = $this->createUploadedFile($this->createSourceFile('def', 'b'), range: 'bytes 3-5/9');

        self::assertSame(UPLOAD_ERR_PARTIAL, $second->error);

        $third = $this->createUploadedFile($this->createSourceFile('ghi', 'c'), range: 'bytes 6-8/9');

        self::assertSame(UPLOAD_ERR_OK, $third->error);
        self::assertFalse($third->isPartial());
        self::assertTrue($third->isCompleted());

        self::assertSame('abcdefghi', file_get_contents($third->tempName));
    }

    /**
     * A first chunk means a new upload, so whatever a previous attempt left behind under the same name is dropped
     * instead of being prepended to it.
     */
    public function testAFirstChunkDiscardsAnAbortedUpload(): void
    {
        $this->createUploadedFile($this->createSourceFile('abc', 'a'), range: 'bytes 0-2/9');

        $restarted = $this->createUploadedFile($this->createSourceFile('xyz', 'b'), range: 'bytes 0-2/3');

        self::assertSame(UPLOAD_ERR_OK, $restarted->error);
        self::assertSame('xyz', file_get_contents($restarted->tempName));
    }

    public function testAnUploadOverTheMaximumSizeIsRefused(): void
    {
        $source = $this->createSourceFile('abc');
        $file = $this->createUploadedFile($source, range: 'bytes 0-2/9', config: ['maxSize' => 8]);

        self::assertSame(UPLOAD_ERR_FORM_SIZE, $file->error);
        self::assertSame($source, $file->tempName);
        self::assertCount(0, glob($this->path . '*.tmp'));
    }

    public function testAnUploadAtTheMaximumSizeIsAccepted(): void
    {
        $file = $this->createUploadedFile($this->createSourceFile('abc'), range: 'bytes 0-2/3', config: ['maxSize' => 3]);

        self::assertSame(UPLOAD_ERR_OK, $file->error);
    }

    public function testAChunkThatCannotBeReadIsReported(): void
    {
        $file = $this->createUploadedFile($this->path . 'does-not-exist', range: 'bytes 0-2/3');

        self::assertSame(UPLOAD_ERR_CANT_WRITE, $file->error);
    }

    /**
     * A browser that aborts mid-upload leaves PHP with `UPLOAD_ERR_PARTIAL` and an empty `tmp_name`, which every
     * path that touches the file has to survive — `fopen('')` is a `ValueError`, not a `false`.
     *
     * @see https://github.com/davidhirtz/yii2-monorepo/issues/27
     */
    public function testAnAbortedUploadIsReportedRatherThanThrowing(): void
    {
        $file = $this->createUploadedFile('', range: 'bytes 0-2/9', config: [
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => 0,
        ]);

        self::assertSame(UPLOAD_ERR_PARTIAL, $file->error);
        self::assertFalse($file->isCompleted());
        self::assertFalse($file->saveAs($this->path . 'target.txt', false));
        self::assertCount(0, glob($this->path . '*.tmp'));

        // the uploader must not be told to send the next chunk of an upload that broke
        self::assertFalse($file->isPartial());
    }

    public function testAnUploadThatExceededTheServerLimitIsReported(): void
    {
        $file = $this->createUploadedFile('', config: [
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ]);

        self::assertSame(UPLOAD_ERR_INI_SIZE, $file->error);
        self::assertFalse($file->isCompleted());
        self::assertFalse($file->saveAs($this->path . 'target.txt', false));
    }

    public function testSaveAsMovesACompletedUpload(): void
    {
        $file = $this->createUploadedFile($this->createSourceFile('abc'), range: 'bytes 0-2/3');
        $target = $this->path . 'target.txt';

        self::assertTrue($file->saveAs($target, false));
        self::assertSame('abc', file_get_contents($target));
    }

    public function testSaveAsRefusesAnIncompleteUpload(): void
    {
        $file = $this->createUploadedFile($this->createSourceFile('abc'), range: 'bytes 0-2/9');

        self::assertFalse($file->saveAs($this->path . 'target.txt', false));
        self::assertFileDoesNotExist($this->path . 'target.txt');
    }

    public function testTheGarbageCollectorOnlyRemovesExpiredFiles(): void
    {
        $expired = $this->path . 'expired.tmp';
        $fresh = $this->path . 'fresh.tmp';

        file_put_contents($expired, 'old');
        file_put_contents($fresh, 'new');
        touch($expired, time() - 90000);

        $file = $this->createUploadedFile($this->createSourceFile('abc'), range: 'bytes 0-2/3', config: [
            'gcProbability' => 100,
        ]);

        self::assertTrue($file->saveAs($this->path . 'target.txt'));

        self::assertFileDoesNotExist($expired);
        self::assertFileExists($fresh);
    }

    public function testGetInstanceReadsTheFileOfAModelAttribute(): void
    {
        $source = $this->createSourceFile('abc');

        $_FILES[User::instance()->formName()] = [
            'error' => ['upload' => UPLOAD_ERR_OK],
            'full_path' => ['upload' => 'folder/test.txt'],
            'name' => ['upload' => 'test.txt'],
            'size' => ['upload' => 3],
            'tmp_name' => ['upload' => $source],
            'type' => ['upload' => 'text/plain'],
        ];

        $file = ChunkedUploadedFile::getInstance(User::instance(), 'upload');

        self::assertSame('test.txt', $file?->name);
        self::assertSame('text/plain', $file->type);
        self::assertNull(ChunkedUploadedFile::getInstance(User::instance(), 'missing'));
    }

    public function testGetInstanceByNameReadsAFlatFile(): void
    {
        $source = $this->createSourceFile('abc');

        $_FILES['upload'] = [
            'error' => UPLOAD_ERR_OK,
            'full_path' => 'folder/test.txt',
            'name' => 'test.txt',
            'size' => 3,
            'tmp_name' => $source,
            'type' => 'text/plain',
        ];

        self::assertSame('test.txt', ChunkedUploadedFile::getInstanceByName('upload')?->name);
        self::assertNull(ChunkedUploadedFile::getInstanceByName('other'));
    }

    public function testMultipleFilesAreRefused(): void
    {
        $this->expectException(InvalidCallException::class);
        ChunkedUploadedFile::getInstances(User::instance(), 'upload');
    }

    public function testMultipleFilesByNameAreRefused(): void
    {
        $this->expectException(InvalidCallException::class);
        ChunkedUploadedFile::getInstancesByName('upload');
    }

    private function createSourceFile(string $content, string $name = 'chunk'): string
    {
        $path = $this->path . "$name.part";
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createUploadedFile(
        string $tempName,
        ?int $size = null,
        ?string $range = null,
        array $config = [],
    ): ChunkedUploadedFile {
        $headers = Yii::$app->getRequest()->getHeaders();
        $headers->remove('content-range');

        if ($range !== null) {
            $headers->set('content-range', $range);
        }

        return new ChunkedUploadedFile([
            'name' => 'upload.txt',
            'error' => UPLOAD_ERR_OK,
            'tempName' => $tempName,
            'size' => $size ?? 0,
            'partialUploadPath' => $this->path,
            'gcProbability' => 0,
            ...$config,
        ]);
    }
}
