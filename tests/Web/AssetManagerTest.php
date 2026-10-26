<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Composer\InstalledVersions;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\AssetManager;
use Override;
use Yii;

class AssetManagerTest extends TestCase
{
    public function testTheApplicationUsesTheAssetManager(): void
    {
        self::assertInstanceOf(AssetManager::class, Yii::$app->getAssetManager());
    }

    public function testAPackageUpdateChangesTheHash(): void
    {
        $root = $this->getSkeletonPath();
        $path = "$root/resources/assets/dist";

        $before = $this->createAssetManager([$root => 'a1b2c3'])->getHash($path);
        $after = $this->createAssetManager([$root => 'd4e5f6'])->getHash($path);

        self::assertNotSame($before, $after);
        self::assertSame($before, $this->createAssetManager([$root => 'a1b2c3'])->getHash($path));
    }

    public function testAPackageHashesWithItsInstalledReference(): void
    {
        $installPath = realpath((string)InstalledVersions::getInstallPath('yiisoft/yii2'));
        self::assertNotFalse($installPath);

        $expected = $this->createAssetManager([
            $installPath => (string)InstalledVersions::getReference('yiisoft/yii2'),
        ]);

        $path = "$installPath/assets";
        self::assertSame($expected->getHash($path), $this->createAssetManager()->getHash($path));
        self::assertNotSame($this->createAssetManager([])->getHash($path), $this->createAssetManager()->getHash($path));
    }

    public function testAPathOutsideEveryPackageHashesWithoutAReference(): void
    {
        $path = Yii::getAlias('@runtime');

        $expected = sprintf('%x', crc32($path . filemtime($path) . Yii::getVersion() . '|'));
        self::assertSame($expected, $this->createAssetManager([])->getHash($path));
    }

    public function testAFileHashesWithItsPackage(): void
    {
        $root = $this->getSkeletonPath();
        $file = "$root/resources/assets/dist/js/admin.js";

        self::assertNotSame(
            $this->createAssetManager([$root => 'a1b2c3'])->getHash($file),
            $this->createAssetManager([])->getHash($file),
        );
    }

    private function getSkeletonPath(): string
    {
        $path = realpath((string)Yii::getAlias('@skeleton/..'));
        self::assertNotFalse($path);

        return $path;
    }

    /**
     * @param array<string, string>|null $references
     */
    private function createAssetManager(?array $references = null): TestAssetManager
    {
        return new TestAssetManager($references);
    }
}

class TestAssetManager extends AssetManager
{
    /**
     * @param array<string, string>|null $fakeReferences
     */
    public function __construct(private readonly ?array $fakeReferences)
    {
        parent::__construct([
            'basePath' => '@runtime',
            'baseUrl' => '/assets',
        ]);
    }

    public function getHash(string $path): string
    {
        return $this->hash($path);
    }

    #[Override]
    protected function getPackageReferences(): array
    {
        return $this->fakeReferences ?? parent::getPackageReferences();
    }
}
