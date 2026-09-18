<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\NamespaceHelper;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class NamespaceHelperTest extends TestCase
{
    /**
     * The project's own prefix is not aliased at all: `vendor/yiisoft/extensions.php` holds an alias for every
     * installed extension and never for the root package, which is what Composer's map answers for instead.
     */
    public function testTheRootPackageNamespaceResolvesWithoutAnAlias(): void
    {
        self::assertFalse(Yii::getAlias('@App/Controllers', false));

        self::assertSame(
            realpath((string)Yii::getAlias('@app')) . '/Migrations',
            NamespaceHelper::getPath('App\Migrations'),
        );
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
