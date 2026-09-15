<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Base;

use Hirtz\Skeleton\Base\RootPackage;
use Hirtz\Skeleton\Helpers\FileHelper;
use Override;
use PHPUnit\Framework\TestCase;

class RootPackageTest extends TestCase
{
    private string $basePath = '';

    #[Override]
    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/' . uniqid('root-package-', true);
        FileHelper::createDirectory($this->basePath);

        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function testTheRootPackageIsWiredUpByHand(): void
    {
        $this->writePackage();
        $package = $this->createRootPackage();

        self::assertSame('Hirtz\Cms\Bootstrap', $package->getBootstrap());
        self::assertSame(['@Hirtz/Cms' => "$this->basePath/src"], $package->getAliases());
    }

    public function testAProjectIsLeftAlone(): void
    {
        $this->writePackage();
        $package = $this->createRootPackage(['name' => 'davidhirtz/yii2-monorepo', 'type' => 'project']);

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    public function testAnotherPackageBesideTheBasePathIsNotTheRoot(): void
    {
        // the tests run from inside a bundle directory of the monorepo, whose extensions.php bootstraps the bundle
        $this->writePackage();
        $package = $this->createRootPackage(['name' => 'davidhirtz/yii2-skeleton', 'type' => 'yii2-extension']);

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    public function testAPackageWithoutABootstrapStillGetsItsAlias(): void
    {
        $this->writePackage(['name' => 'davidhirtz/yii2-cms', 'autoload' => ['psr-4' => ['Hirtz\\Cms\\' => 'src']]]);
        $package = $this->createRootPackage();

        self::assertNull($package->getBootstrap());
        self::assertSame(['@Hirtz/Cms' => "$this->basePath/src"], $package->getAliases());
    }

    public function testAMissingPackageIsNotAnError(): void
    {
        $package = $this->createRootPackage();

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    /**
     * @param array{name?: string, type?: string}|null $rootPackage
     */
    private function createRootPackage(?array $rootPackage = null): RootPackage
    {
        return new RootPackage($this->basePath, $rootPackage ?? ['name' => 'davidhirtz/yii2-cms', 'type' => 'yii2-extension']);
    }

    /**
     * @param array<string, mixed>|null $package
     */
    private function writePackage(?array $package = null): void
    {
        $package ??= [
            'name' => 'davidhirtz/yii2-cms',
            'autoload' => ['psr-4' => ['Hirtz\\Cms\\' => 'src']],
            'extra' => ['bootstrap' => 'Hirtz\\Cms\\Bootstrap'],
        ];

        file_put_contents("$this->basePath/composer.json", (string)json_encode($package));
    }
}
