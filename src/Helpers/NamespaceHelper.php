<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Composer\Autoload\ClassLoader;
use Yii;

/**
 * Yii resolves a namespace to a directory through an alias of the same name, which `yiisoft/yii2-composer` writes
 * into `vendor/yiisoft/extensions.php` for every installed extension and never for the root package — so a
 * project's own `App\Migrations` had nothing to resolve against. Composer's autoloader holds the same mapping for
 * both, without an alias and without reading anything from disk, and therefore answers first.
 */
class NamespaceHelper
{
    /**
     * @return string|null the directory the namespace maps to, which need not exist: the first migration of a
     * project is created in a directory that is not there yet.
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
