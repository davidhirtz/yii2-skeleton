<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Composer\Autoload\ClassLoader;
use Hirtz\Skeleton\Helpers\NamespaceHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class NamespaceHelperTest extends TestCase
{
    /**
     * The project's own prefix is not aliased at all: `vendor/yiisoft/extensions.php` holds an alias for every
     * installed extension and never for the root package, which is what Composer's map answers for instead. The
     * prefix is registered here rather than read off the root package, since the root is the monorepo in one
     * checkout and the bundle itself in a standalone install.
     */
    public function testARootPackageNamespaceResolvesWithoutAnAlias(): void
    {
        $loaders = ClassLoader::getRegisteredLoaders();
        $loader = reset($loaders);
        self::assertInstanceOf(ClassLoader::class, $loader);

        $basePath = (string)Yii::getAlias('@runtime');
        $loader->setPsr4('Scratch\\', $basePath);

        try {
            self::assertFalse(Yii::getAlias('@Scratch/Migrations', false));
            self::assertSame(realpath($basePath) . '/Migrations', NamespaceHelper::getPath('Scratch\Migrations'));
        } finally {
            $loader->setPsr4('Scratch\\', []);
        }
    }

    public function testABundleNamespaceResolvesToItsSourceTree(): void
    {
        self::assertSame(
            realpath((string)Yii::getAlias('@skeleton') . '/Migrations'),
            NamespaceHelper::getPath('Hirtz\Skeleton\Migrations'),
        );
    }

    /**
     * A namespace the autoloader does not know falls back to the alias table, which is where a bundle installed
     * without Composer — or a namespace a project aliased by hand — comes from.
     */
    public function testANamespaceMissingFromTheAutoloaderFallsBackToItsAlias(): void
    {
        Yii::setAlias('@Scratch', '/scratch');

        try {
            self::assertSame('/scratch/Migrations', NamespaceHelper::getPath('Scratch\Migrations'));
        } finally {
            Yii::setAlias('@Scratch', null);
        }
    }

    public function testANamespaceNothingKnowsAboutIsNull(): void
    {
        self::assertNull(NamespaceHelper::getPath('Scratch\Migrations'));
    }
}
