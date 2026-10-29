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
     * `extensions.php` never aliases the root package, which is the monorepo here and the bundle in a standalone
     * install.
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
     * A bundle installed without Composer, or a namespace aliased by hand, is known only to the alias table.
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
