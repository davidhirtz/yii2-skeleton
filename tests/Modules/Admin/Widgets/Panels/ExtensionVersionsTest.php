<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ExtensionVersions;
use Hirtz\Skeleton\Test\TestCase;

class ExtensionVersionsTest extends TestCase
{
    public function testTheDefaultBlockListLeavesOutTheFrameworkAndTheDateTimeBehavior(): void
    {
        $installed = array_keys(VersionHelper::getExtensions());

        self::assertContains('yiisoft/yii2-debug', $installed);
        self::assertContains('davidhirtz/yii2-datetime-behavior', $installed);

        $widget = ExtensionVersions::make();
        $widget->render();

        $listed = array_keys($widget->getExtensions());

        self::assertNotContains('yiisoft/yii2-debug', $listed);
        self::assertNotContains('davidhirtz/yii2-datetime-behavior', $listed);
        self::assertContains('davidhirtz/yii2-skeleton', $listed);
    }

    public function testAProjectCanBlockAnExtensionOfItsOwn(): void
    {
        $widget = ExtensionVersions::make();
        $widget->excluded = ['*/yii2-skeleton'];
        $widget->render();

        self::assertArrayNotHasKey('davidhirtz/yii2-skeleton', $widget->getExtensions());
        self::assertArrayHasKey('yiisoft/yii2-debug', $widget->getExtensions());
    }

    public function testTheVersionIsTheBadgesTooltipRatherThanItsText(): void
    {
        $html = ExtensionVersions::make()
            ->extensions(['davidhirtz/yii2-skeleton' => '3.0.0'])
            ->render();

        self::assertStringContainsString('class="badge-list"', $html);
        self::assertStringContainsString('class="badge badge-info"', $html);
        self::assertStringContainsString('data-tooltip=""', $html);
        self::assertStringContainsString('title="3.0.0"', $html);
        self::assertStringContainsString('>yii2-skeleton</span>', $html);
        self::assertStringNotContainsString('davidhirtz/yii2-skeleton<', $html);
    }

    public function testNothingIsRenderedWithoutExtensions(): void
    {
        self::assertSame('', ExtensionVersions::make()->extensions([])->render());
    }
}
