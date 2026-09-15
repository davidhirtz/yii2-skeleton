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
        FileHelper::createDirectory("$this->basePath/vendor/yiisoft");

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

    public function testAnInstalledPackageIsLeftToComposer(): void
    {
        $this->writePackage();
        $this->writeExtensions(['davidhirtz/yii2-cms' => ['name' => 'davidhirtz/yii2-cms']]);

        $package = $this->createRootPackage();

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    public function testAPackageWithoutABootstrapIsLeftAlone(): void
    {
        $this->writePackage(['name' => 'davidhirtz/yii2-project', 'autoload' => ['psr-4' => ['App\\' => 'app']]]);
        $package = $this->createRootPackage();

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    public function testAMissingPackageIsNotAnError(): void
    {
        $package = $this->createRootPackage();

        self::assertNull($package->getBootstrap());
        self::assertSame([], $package->getAliases());
    }

    private function createRootPackage(): RootPackage
    {
        return new RootPackage($this->basePath, "$this->basePath/vendor");
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

    /**
     * @param array<string, mixed> $extensions
     */
    private function writeExtensions(array $extensions): void
    {
        file_put_contents(
            "$this->basePath/vendor/yiisoft/extensions.php",
            '<?php return ' . var_export($extensions, true) . ';'
        );
    }
}
