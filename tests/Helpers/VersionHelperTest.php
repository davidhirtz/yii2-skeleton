<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class VersionHelperTest extends TestCase
{
    /**
     * The platform-version exclusions are the registry's own, since only the registry computes one.
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
        self::assertSame(substr(self::git('rev-parse HEAD'), 0, 8), VersionHelper::getApplicationReference());
    }

    public function testTheBranchIsReadFromTheCheckout(): void
    {
        self::assertSame(self::git('symbolic-ref --short -q HEAD'), VersionHelper::getApplicationBranch());
    }

    public function testALinkedWorktreeReadsItsBranchFromTheMainRepository(): void
    {
        $root = (string)Yii::getAlias('@root');
        $path = Yii::getAlias('@runtime') . '/version-helper-' . uniqid();
        $reference = sha1('loose');

        FileHelper::createDirectory("$path/main/.git/refs/heads");
        FileHelper::createDirectory("$path/main/.git/worktrees/linked");
        FileHelper::createDirectory("$path/linked");

        file_put_contents("$path/main/.git/refs/heads/main", "$reference\n");
        file_put_contents("$path/main/.git/packed-refs", sha1('packed') . " refs/heads/packed\n");
        file_put_contents("$path/main/.git/worktrees/linked/HEAD", "ref: refs/heads/main\n");
        file_put_contents("$path/main/.git/worktrees/linked/commondir", "../..\n");
        file_put_contents("$path/linked/.git", "gitdir: ../main/.git/worktrees/linked\n");

        try {
            Yii::setAlias('@root', "$path/linked");

            self::assertSame(substr($reference, 0, 8), VersionHelper::getApplicationReference());
            self::assertSame('main', VersionHelper::getApplicationBranch());

            file_put_contents("$path/main/.git/worktrees/linked/HEAD", "ref: refs/heads/packed\n");

            self::assertSame(substr(sha1('packed'), 0, 8), VersionHelper::getApplicationReference());
        } finally {
            Yii::setAlias('@root', $root);
            FileHelper::removeDirectory($path);
        }
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

    private static function git(string $command): string
    {
        exec('git -C ' . escapeshellarg((string)Yii::getAlias('@root')) . " $command 2>/dev/null", $output, $code);

        if ($code !== 0 || !isset($output[0])) {
            self::markTestSkipped("`git $command` answers nothing here.");
        }

        return $output[0];
    }
}
