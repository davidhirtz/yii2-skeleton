<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Modules\Admin\Widgets\DirectoryAlert;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class DirectoryAlertTest extends TestCase
{
    public function testTheAlertIsInvisibleWhileEveryDirectoryIsWritable(): void
    {
        $alert = DirectoryAlert::make();

        self::assertSame('', $alert->render());
        self::assertFalse($alert->isVisible());
        self::assertSame([], $alert->getUnwritable());
    }

    public function testAMissingDirectoryIsReportedByItsResolvedPath(): void
    {
        $alert = DirectoryAlert::make();
        $alert->directories = ['@runtime', '@runtime/does-not-exist'];

        $html = $alert->render();

        self::assertStringContainsString('data-alert="danger"', $html);
        self::assertStringContainsString('This directory is not writable', $html);
        self::assertStringContainsString(Yii::getAlias('@runtime') . '/does-not-exist', $html);
        self::assertStringNotContainsString('@runtime/does-not-exist', $html);
    }

    public function testAnUnresolvableAliasIsReportedAsItself(): void
    {
        $alert = DirectoryAlert::make();
        $alert->directories = ['@nothing-defines-this'];
        $alert->render();

        self::assertSame(['@nothing-defines-this'], $alert->getUnwritable());
    }

    public function testSeveralDirectoriesAreReportedInThePlural(): void
    {
        $html = DirectoryAlert::make()
            ->unwritable(['/srv/app/runtime', '/srv/app/web/assets'])
            ->render();

        self::assertStringContainsString('These directories are not writable', $html);
        self::assertStringContainsString('/srv/app/runtime, /srv/app/web/assets', $html);
    }

    public function testAnUnwritableDirectoryIsDetectedOnDisk(): void
    {
        $path = Yii::getAlias('@runtime') . '/read-only-' . getmypid();

        self::assertTrue(mkdir($path, 0o555, true));

        try {
            $alert = DirectoryAlert::make();
            $alert->directories = [$path];

            self::assertStringContainsString('not writable', $alert->render());
            self::assertSame([$path], $alert->getUnwritable());
        } finally {
            chmod($path, 0o755);
            rmdir($path);
        }
    }
}
