<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Upload;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Yii;

class UploadTest extends TestCase
{
    private Upload $upload;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->set('upload', [
            'class' => Upload::class,
            'tempPath' => '@runtime/test-uploads',
        ]);

        $this->upload = Upload::getComponent();
        FileHelper::createDirectory($this->upload->tempPath);
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->upload->tempPath);
        parent::tearDown();
    }

    public function testOnlyAnExpiredFileIsCollected(): void
    {
        $expired = $this->createTempFile('expired.tmp', time() - $this->upload->tempLifetime - 1);
        $fresh = $this->createTempFile('fresh.tmp');

        self::assertSame(1, $this->upload->collectGarbage());

        self::assertFileDoesNotExist($expired);
        self::assertFileExists($fresh);
    }

    /**
     * A small installation has no cron, so an upload collects — but once per session and lifetime window rather
     * than on every request.
     */
    public function testTheCollectorRunsOncePerSession(): void
    {
        $this->createTempFile('expired.tmp', time() - $this->upload->tempLifetime - 1);

        self::assertSame(1, $this->upload->collectGarbageOncePerSession());

        $this->createTempFile('second.tmp', time() - $this->upload->tempLifetime - 1);

        self::assertSame(0, $this->upload->collectGarbageOncePerSession());
        self::assertFileExists($this->upload->tempPath . 'second.tmp');
    }

    public function testAWindowThatHasPassedCollectsAgain(): void
    {
        $this->upload->collectGarbageOncePerSession();
        $this->getWebSession()->set(Upload::SESSION_KEY, time() - $this->upload->tempLifetime - 1);

        $this->createTempFile('expired.tmp', time() - $this->upload->tempLifetime - 1);

        self::assertSame(1, $this->upload->collectGarbageOncePerSession());
    }

    public function testTheCollectorIsSwitchedOff(): void
    {
        $this->upload->enableGarbageCollection = false;
        $expired = $this->createTempFile('expired.tmp', time() - $this->upload->tempLifetime - 1);

        self::assertSame(0, $this->upload->collectGarbageOncePerSession());
        self::assertFileExists($expired);
    }

    /**
     * The URL is derived from where the files are kept, so a path outside the web root has to be named itself.
     */
    public function testTheBaseUrlFollowsThePath(): void
    {
        Yii::$app->set('upload', [
            'class' => Upload::class,
            'path' => '@webroot/files/attachments',
        ]);

        self::assertSame('/files/attachments/', Upload::getComponent()->baseUrl);
    }

    private function createTempFile(string $name, ?int $time = null): string
    {
        $path = $this->upload->tempPath . $name;
        file_put_contents($path, 'x');

        if ($time !== null) {
            touch($path, $time);
        }

        return $path;
    }
}
