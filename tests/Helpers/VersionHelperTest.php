<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class VersionHelperTest extends TestCase
{
    /**
     * The exclusions are the framework's own extensions and a package that is a dependency rather than a bundle.
     * A version that would distort an installation's *platform version* is the version registry's own exclusion,
     * since only the registry computes one — the badges here report whatever the installation runs.
     */
    public function testThePlatformIsTheBundlesRatherThanTheirDependencies(): void
    {
        self::assertTrue(VersionHelper::isPlatformPackage('davidhirtz/yii2-skeleton'));
        self::assertTrue(VersionHelper::isPlatformPackage('davidhirtz/yii2-vite'));
        self::assertFalse(VersionHelper::isPlatformPackage('yiisoft/yii2-debug'));
        self::assertFalse(VersionHelper::isPlatformPackage('davidhirtz/yii2-datetime-behavior'));

        self::assertTrue(VersionHelper::isPlatformPackage('davidhirtz/yii2-datetime-behavior', ['yiisoft/*']));
        self::assertFalse(VersionHelper::isPlatformPackage('davidhirtz/yii2-skeleton', ['*/yii2-skeleton']));
    }

    public function testTheApplicationReportsItsComposerIdentity(): void
    {
        self::assertStringContainsString('/', VersionHelper::getApplicationName());
        self::assertNotSame('', VersionHelper::getApplicationVersion());
    }

    public function testTheReferenceIsAnAbbreviatedCommitHash(): void
    {
        $reference = VersionHelper::getApplicationReference();

        self::assertNotNull($reference);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $reference);
    }

    public function testTheUpdatedTimeIsNotInTheFuture(): void
    {
        $timestamp = VersionHelper::getApplicationUpdatedAt();

        self::assertNotNull($timestamp);
        self::assertLessThanOrEqual(time(), $timestamp);
    }

    public function testTheApplicationPrefersGitOverComposer(): void
    {
        // `vendor/composer/installed.php` is only rewritten by `composer install`, so it lags behind the checkout
        $head = trim((string)file_get_contents(Yii::getAlias('@root') . '/.git/HEAD'));

        if (!str_starts_with($head, 'ref:')) {
            self::markTestSkipped('The repository is in a detached HEAD state.');
        }

        $reference = trim((string)file_get_contents(Yii::getAlias('@root') . '/.git/' . trim(substr($head, 4))));

        self::assertSame(substr($reference, 0, 8), VersionHelper::getApplicationReference());
    }

    public function testTheInstalledExtensionsAreSortedAndVersioned(): void
    {
        $extensions = VersionHelper::getExtensions();

        self::assertArrayHasKey('davidhirtz/yii2-skeleton', $extensions);
        self::assertNotSame('', $extensions['davidhirtz/yii2-skeleton']);

        $names = array_keys($extensions);
        sort($names);

        self::assertSame($names, array_keys($extensions));
    }

    public function testTheInstalledExtensionsCarryTheirReference(): void
    {
        $extensions = VersionHelper::getInstalledExtensions();

        self::assertSame(array_keys(VersionHelper::getExtensions()), array_keys($extensions));

        foreach ($extensions as $name => $extension) {
            self::assertSame(VersionHelper::getExtensions()[$name], $extension['version']);
            self::assertTrue($extension['reference'] === null || preg_match('/^[0-9a-f]{8}$/', $extension['reference']) === 1, $name);
        }

        // a path repository records the checked out commit, so the skeleton's own reference is known here
        self::assertNotNull($extensions['davidhirtz/yii2-skeleton']['reference']);
    }
}
