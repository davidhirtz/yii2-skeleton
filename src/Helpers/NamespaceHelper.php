<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Composer\Autoload\ClassLoader;
use Yii;

/**
 * Asks Composer's autoloader, since `yiisoft/yii2-composer` writes no alias for the root package's `App\Migrations`.
 */
class NamespaceHelper
{
    /**
     * The directory need not exist: a project's first migration is created in one that is not there yet.
     */
    public static function getPath(string $namespace): ?string
    {
        $parts = explode('\\', trim($namespace, '\\'));
        $suffix = '';
        $candidate = null;

        while ($parts) {
            $prefix = implode('\\', $parts) . '\\';

            foreach (ClassLoader::getRegisteredLoaders() as $loader) {
                foreach ($loader->getPrefixesPsr4()[$prefix] ?? [] as $path) {
                    // Composer keeps the paths relative to `vendor/composer`, so `realpath()` is what makes the
                    // `../..` of the root package's own prefix printable. It cannot resolve the suffix, which is
                    // the directory that may still be missing.
                    $path = rtrim((realpath($path) ?: $path) . "/$suffix", '/');

                    if (is_dir($path)) {
                        return $path;
                    }

                    $candidate ??= $path;
                }
            }

            $suffix = array_pop($parts) . ($suffix === '' ? '' : "/$suffix");
        }

        $alias = Yii::getAlias('@' . str_replace('\\', '/', trim($namespace, '\\')), false);

        return is_string($alias) ? $alias : $candidate;
    }
}
