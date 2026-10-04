<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Traits;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\InfoList;

class ContainerTraitTest extends TestCase
{
    public function testAWidgetRendersInItsContainer(): void
    {
        $html = InfoList::make()
            ->addAttributes(['id' => 'info'])
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringStartsWith('<div id="info" class="container">', $html);
    }

    public function testAWidgetWithoutItsContainerRendersBare(): void
    {
        $html = InfoList::make()
            ->container(false)
            ->addAttributes(['id' => 'info'])
            ->addRow('PHP', '8.5.0')
            ->render();

        self::assertStringNotContainsString('container', $html);
        self::assertStringNotContainsString('id="info"', $html);
        self::assertStringContainsString('<div class="form-content">8.5.0</div>', $html);
    }

    public function testAnEmptyWidgetWithoutItsContainerRendersNothing(): void
    {
        self::assertSame('', InfoList::make()->container(false)->render());
    }

    public function testColumnsHoldWidgetsWithoutTheirContainers(): void
    {
        $html = Container::make()
            ->columns()
            ->content(
                InfoList::make()->container(false)->addRow('PHP', '8.5.0'),
                InfoList::make()->container(false)->addRow('Yii', '2.0.53'),
            )
            ->render();

        self::assertStringStartsWith('<div class="container-columns container">', $html);
        self::assertSame(1, substr_count($html, 'container"'));
        self::assertSame(2, substr_count($html, 'class="card"'));
    }
}
