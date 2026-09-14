<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ExtensionVersions;
use Hirtz\Skeleton\Test\TestCase;

class ExtensionVersionsTest extends TestCase
{
    public function testTheFrameworksOwnExtensionsAreLeftOut(): void
    {
        $isFramework = fn (string $name) => str_starts_with($name, 'yiisoft/');

        self::assertNotSame([], array_filter(array_keys(VersionHelper::getExtensions()), $isFramework));

        $widget = ExtensionVersions::make();
        $widget->render();

        self::assertSame([], array_filter(array_keys($widget->getExtensions()), $isFramework));
    }

    public function testEveryExtensionIsRenderedAsABadgeWithoutItsVendor(): void
    {
        $html = ExtensionVersions::make()
            ->extensions(['davidhirtz/yii2-skeleton' => '3.0.0', 'acme/yii2-foo' => 'dev-main'])
            ->render();

        self::assertStringContainsString('class="badge-list"', $html);
        self::assertStringContainsString('title="davidhirtz/yii2-skeleton"', $html);
        self::assertStringContainsString('yii2-skeleton<span class="badge-value">3.0.0</span>', $html);
        self::assertStringContainsString('yii2-foo<span class="badge-value">dev-main</span>', $html);
        self::assertStringNotContainsString('davidhirtz/yii2-skeleton<', $html);
    }

    public function testTheCardIsOmittedWithoutExtensions(): void
    {
        self::assertSame('', ExtensionVersions::make()->extensions([])->render());
    }

    public function testTheSkeletonIsReportedForThisInstallation(): void
    {
        self::assertStringContainsString('yii2-skeleton', ExtensionVersions::make()->render());
    }
}
