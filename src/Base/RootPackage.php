<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

use Composer\InstalledVersions;

/**
 * Composer never writes the root package into `vendor/yiisoft/extensions.php`, so a bundle run on its own lacks its
 * namespace alias and its `Bootstrap`.
 */
class RootPackage
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $package = null;

    /**
     * @param array{name?: string, type?: string}|null $rootPackage
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
     * The name has to match, since a test run from a bundle inside a monorepo sees that bundle's composer.json
     * while Composer's root is the monorepo, whose extensions.php already bootstraps it.
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
