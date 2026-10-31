<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Composer\InstalledVersions;
use Yii;

/**
 * Prefers `.git` where one exists, since `vendor/composer/installed.php` lags behind the checked out commit.
 */
class VersionHelper
{
    /**
     * Matched with `fnmatch()`; the one `davidhirtz/` package is v2's date dependency, which a project may still
     * require.
     *
     * @var list<string>
     */
    final public const array EXCLUDED_PACKAGES = [
        'yiisoft/*',
        'davidhirtz/yii2-datetime-behavior',
    ];

    /**
     * @param list<string> $excluded
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
     * `null` without a `.git` directory or on a detached HEAD.
     */
    public static function getApplicationBranch(): ?string
    {
        $path = self::getGitPath();
        $head = $path === null ? false : @file_get_contents("$path/HEAD");

        return is_string($head) && preg_match('#^ref:\s*refs/heads/(.+)$#', trim($head), $matches)
            ? $matches[1]
            : null;
    }

    /**
     * The commit's checkout time, or the last `composer install`.
     */
    public static function getApplicationUpdatedAt(): ?int
    {
        $time = self::getGitHead()['time'] ?? @filemtime(Yii::getAlias('@vendor') . '/composer/installed.php');
        return is_int($time) ? $time : null;
    }

    /**
     * @return array<string, string>
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
     * The reference is the only thing that tells two `dev-main` installs apart.
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
        $commonPath = self::getGitCommonPath($path);
        $file = str_starts_with($head, 'ref:')
            ? $commonPath . '/' . trim(substr($head, 4))
            : "$path/HEAD";

        $reference = @file_get_contents($file);
        $time = @filemtime($file);

        if (!is_string($reference) || !is_int($time)) {
            return self::getPackedRef($commonPath, $head);
        }

        return [
            'reference' => trim($reference),
            'time' => $time,
        ];
    }

    /**
     * A ref `git gc` packed exists only in `packed-refs`, whose modification time is the packing's, not the commit's.
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

        if (!is_string($pointer) || !str_starts_with($pointer, 'gitdir:')) {
            return null;
        }

        $gitPath = trim(substr($pointer, 7));
        return str_starts_with($gitPath, '/') ? $gitPath : dirname($path) . "/$gitPath";
    }

    /**
     * A linked worktree's directory holds its `HEAD`, the main repository's (named by `commondir`) the branches.
     */
    private static function getGitCommonPath(string $path): string
    {
        $commonPath = @file_get_contents("$path/commondir");

        if (!is_string($commonPath)) {
            return $path;
        }

        $commonPath = trim($commonPath);
        return str_starts_with($commonPath, '/') ? $commonPath : "$path/$commonPath";
    }
}
