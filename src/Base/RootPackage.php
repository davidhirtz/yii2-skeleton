<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base;

/**
 * A bundle installed as a dependency is wired up by `vendor/yiisoft/extensions.php`, which Composer writes from
 * its `extra.bootstrap` and its autoload. Composer never lists the root package there, so a bundle tested or run
 * on its own would have neither its namespace alias nor its `Bootstrap` — and therefore none of the migrations,
 * modules and event handlers the bootstrap registers.
 */
class RootPackage
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $package = null;

    public function __construct(
        private readonly string $basePath,
        private readonly string $vendorPath,
    ) {
    }

    /**
     * The `Bootstrap` of the root package, or `null` where there is none to run by hand.
     */
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
     * @return array<string, mixed>
     */
    private function loadPackage(): array
    {
        $json = @file_get_contents("$this->basePath/composer.json");
        $package = is_string($json) ? json_decode($json, true) : null;

        // A package without one is a project rather than a bundle, and Yii already knows everything about it.
        if (!is_array($package) || !isset($package['extra']['bootstrap'])) {
            return [];
        }

        return $this->isInstalled($package['name'] ?? null) ? [] : $package;
    }

    private function isInstalled(mixed $name): bool
    {
        $file = "$this->vendorPath/yiisoft/extensions.php";
        $extensions = is_file($file) ? require $file : [];

        return is_array($extensions) && isset($extensions[$name]);
    }
}
