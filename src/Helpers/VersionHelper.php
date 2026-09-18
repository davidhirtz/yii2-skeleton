<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Composer\InstalledVersions;
use Yii;

/**
 * The versions a running installation reports about itself. Composer is the source of truth, since a deployed
 * project rarely ships a `.git` directory; where one exists it is preferred, because `vendor/composer/installed.php`
 * is only rewritten by `composer install` and therefore lags behind the checked out commit.
 */
class VersionHelper
{
    /**
     * @var list<string> the installed packages that are not the platform, matched against the full name with
     * `fnmatch()`. The framework's own extensions carry its version, and the two `davidhirtz/` packages are
     * dependencies rather than bundles.
     */
    final public const array EXCLUDED_PACKAGES = [
        'yiisoft/*',
        'davidhirtz/yii2-datetime-behavior',
        'davidhirtz/yii2-vite',
    ];

    /**
     * Which of the installed extensions make up the platform, and therefore what an installation's version is
     * read from. One answer, since the version registry asks it as well as the system page.
     *
     * @param list<string> $excluded patterns to use in place of {@see static::EXCLUDED_PACKAGES}
     */
    public static function isPlatformPackage(string $name, array $excluded = self::EXCLUDED_PACKAGES): bool
    {
        foreach ($excluded as $pattern) {
            if (fnmatch($pattern, $name)) {
                return false;
            }
        }

        return true;
    }

    public static function getApplicationName(): string
    {
        return InstalledVersions::getRootPackage()['name'];
    }

    public static function getApplicationVersion(): string
    {
        return InstalledVersions::getRootPackage()['pretty_version'];
    }

    public static function getApplicationReference(): ?string
    {
        $reference = self::getGitHead()['reference'] ?? InstalledVersions::getRootPackage()['reference'];
        return is_string($reference) ? substr($reference, 0, 8) : null;
    }

    /**
     * The time the application was last deployed: the commit's checkout time, or the last `composer install`.
     */
    public static function getApplicationUpdatedAt(): ?int
    {
        $time = self::getGitHead()['time'] ?? @filemtime(Yii::getAlias('@vendor') . '/composer/installed.php');
        return is_int($time) ? $time : null;
    }

    /**
     * @return array<string, string> the installed Yii extensions, mapped to their pretty version.
     */
    public static function getExtensions(): array
    {
        $extensions = [];

        foreach (InstalledVersions::getInstalledPackagesByType('yii2-extension') as $package) {
            $extensions[$package] = InstalledVersions::getPrettyVersion($package) ?? '';
        }

        ksort($extensions);
        return $extensions;
    }

    /**
     * The extensions with the commit they were installed from, which is the only thing that tells two `dev-main`
     * installs apart.
     *
     * @return array<string, array{version: string, reference: string|null}>
     */
    public static function getInstalledExtensions(): array
    {
        $extensions = [];

        foreach (InstalledVersions::getInstalledPackagesByType('yii2-extension') as $package) {
            $reference = InstalledVersions::getReference($package);

            $extensions[$package] = [
                'version' => InstalledVersions::getPrettyVersion($package) ?? '',
                'reference' => is_string($reference) && $reference !== '' ? substr($reference, 0, 8) : null,
            ];
        }

        ksort($extensions);
        return $extensions;
    }

    /**
     * @return array{reference: string, time: int}|array{}
     */
    private static function getGitHead(): array
    {
        $path = self::getGitPath();
        $head = $path === null ? false : @file_get_contents("$path/HEAD");

        if ($path === null || !is_string($head)) {
            return [];
        }

        $head = trim($head);
        $file = str_starts_with($head, 'ref:')
            ? $path . '/' . trim(substr($head, 4))
            : "$path/HEAD";

        $reference = @file_get_contents($file);
        $time = @filemtime($file);

        if (!is_string($reference) || !is_int($time)) {
            return self::getPackedRef($path, $head);
        }

        return [
            'reference' => trim($reference),
            'time' => $time,
        ];
    }

    /**
     * A branch whose loose ref was packed away by `git gc` only exists in `packed-refs`, whose modification time is
     * the time of the packing rather than of the commit.
     *
     * @return array{reference: string, time: int}|array{}
     */
    private static function getPackedRef(string $path, string $head): array
    {
        $refs = @file_get_contents("$path/packed-refs");
        $ref = trim(substr($head, 4));

        if (!is_string($refs) || !str_starts_with($head, 'ref:')) {
            return [];
        }

        foreach (explode("\n", $refs) as $line) {
            if (str_ends_with(trim($line), " $ref")) {
                return [
                    'reference' => strtok(trim($line), ' ') ?: '',
                    'time' => (int)@filemtime("$path/packed-refs"),
                ];
            }
        }

        return [];
    }

    private static function getGitPath(): ?string
    {
        $path = Yii::getAlias('@root/.git', false);

        if (!is_string($path)) {
            return null;
        }

        if (is_dir($path)) {
            return $path;
        }

        // A linked worktree records the real directory in a `gitdir:` pointer file.
        $pointer = is_file($path) ? @file_get_contents($path) : false;

        return is_string($pointer) && str_starts_with($pointer, 'gitdir:')
            ? trim(substr($pointer, 7))
            : null;
    }
}
