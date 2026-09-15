<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

use Composer\InstalledVersions;

/**
 * A bundle installed as a dependency is wired up by `vendor/yiisoft/extensions.php`, which Composer never writes
 * the root package into — so a bundle run on its own has neither its namespace alias nor its `Bootstrap`.
 */
class RootPackage
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $package = null;

    /**
     * @param array{name?: string, type?: string}|null $rootPackage what Composer installed as the root package;
     * defaults to `InstalledVersions::getRootPackage()`, which the autoloader already holds in memory
     */
    public function __construct(
        private readonly string $basePath,
        private readonly ?array $rootPackage = null,
    ) {
    }

    public function getBootstrap(): ?string
    {
        $bootstrap = $this->getPackage()['extra']['bootstrap'] ?? null;
        return is_string($bootstrap) ? $bootstrap : null;
    }

    /**
     * The aliases Composer would have written for the package: `@Hirtz/Skeleton` for `Hirtz\Skeleton\` => `src`.
     *
     * @return array<string, string>
     */
    public function getAliases(): array
    {
        $aliases = [];

        foreach ($this->getPackage()['autoload']['psr-4'] ?? [] as $namespace => $path) {
            if (is_string($path)) {
                $name = str_replace('\\', '/', trim((string)$namespace, '\\'));
                $aliases["@$name"] = rtrim("$this->basePath/" . trim($path, '/'), '/');
            }
        }

        return $aliases;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPackage(): array
    {
        return $this->package ??= $this->loadPackage();
    }

    /**
     * Only a root package of type `yii2-extension` is read from disk — a project answers `project` without any I/O.
     * The name has to match too: tests run from inside a bundle directory of a monorepo see that bundle's
     * composer.json beside the base path while Composer's root is the monorepo, whose extensions.php already
     * bootstraps the bundle.
     *
     * @return array<string, mixed>
     */
    private function loadPackage(): array
    {
        $root = $this->rootPackage ?? InstalledVersions::getRootPackage();

        if (($root['type'] ?? null) !== 'yii2-extension') {
            return [];
        }

        $json = @file_get_contents("$this->basePath/composer.json");
        $package = is_string($json) ? json_decode($json, true) : null;

        return is_array($package) && ($package['name'] ?? null) === ($root['name'] ?? null) ? $package : [];
    }
}
