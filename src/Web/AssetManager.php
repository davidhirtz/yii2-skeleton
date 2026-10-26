<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Composer\InstalledVersions;
use Override;
use Yii;

/**
 * Hashes a package's assets with its installed reference: a package update rarely changes the modification time of
 * the source directory, Yii's only marker, so the old copy stayed published.
 */
class AssetManager extends \yii\web\AssetManager
{
    /**
     * @var array<string, string>|null
     */
    private ?array $references = null;

    #[Override]
    protected function hash($path): string
    {
        if (is_callable($this->hashCallback)) {
            return parent::hash($path);
        }

        $directory = is_file($path) ? dirname($path) : $path;
        $reference = $this->getPackageReference($directory);

        return sprintf('%x', crc32($directory . filemtime($path) . $reference . Yii::getVersion() . '|' . $this->linkAssets));
    }

    protected function getPackageReference(string $path): string
    {
        $path = realpath($path);

        if ($path === false) {
            return '';
        }

        $this->references ??= $this->getPackageReferences();

        foreach ($this->references as $installPath => $reference) {
            if (str_starts_with($path . DIRECTORY_SEPARATOR, $installPath . DIRECTORY_SEPARATOR)) {
                return $reference;
            }
        }

        return '';
    }

    /**
     * The root package is left out: its own assets change with every commit, and the modification time covers them.
     *
     * @return array<string, string>
     */
    protected function getPackageReferences(): array
    {
        $root = InstalledVersions::getRootPackage()['name'];
        $references = [];

        foreach (InstalledVersions::getInstalledPackages() as $package) {
            $installPath = $package !== $root ? InstalledVersions::getInstallPath($package) : null;
            $installPath = $installPath !== null ? realpath($installPath) : false;

            if ($installPath !== false) {
                $references[$installPath] = InstalledVersions::getReference($package)
                    ?? InstalledVersions::getPrettyVersion($package)
                    ?? '';
            }
        }

        return $references;
    }
}
