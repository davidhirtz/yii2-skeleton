<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Db\DatabaseComponents;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\MaintenanceInfo;
use Hirtz\Skeleton\Test\TestCase;
use yii\caching\ArrayCache;

class MaintenanceInfoTest extends TestCase
{
    public function testEveryCacheComponentGetsItsOwnFlushButton(): void
    {
        $html = MaintenanceInfo::make()->render();

        foreach (array_keys(CacheComponents::getAll()) as $name) {
            self::assertStringContainsString("system/flush?cache=$name", $html);
        }

        self::assertStringContainsString(ArrayCache::class, $html);
    }

    public function testEveryConnectionGetsItsOwnSchemaButton(): void
    {
        $html = MaintenanceInfo::make()->render();
        $connections = DatabaseComponents::getAll();

        self::assertArrayHasKey('db', $connections);

        foreach (array_keys($connections) as $name) {
            self::assertStringContainsString("system/schema?db=$name", $html);
        }
    }

    public function testTheAssetAndSessionRowsCarryTheirAction(): void
    {
        $html = MaintenanceInfo::make()->render();

        self::assertStringContainsString('system/publish', $html);
        self::assertStringContainsString('system/session-gc', $html);
        self::assertStringContainsString('<div class="form-label">Sessions</div>', $html);
        self::assertSame(4, substr_count($html, 'class="form-action"'));
    }

    public function testTheCacheIsNotListedAsAConnection(): void
    {
        self::assertArrayNotHasKey('cache', DatabaseComponents::getAll());
        self::assertArrayNotHasKey('db', CacheComponents::getAll());
    }

    public function testTheConnectionListIsNotCarriedIntoTheNextApplication(): void
    {
        $connections = DatabaseComponents::getAll();

        $this->reloadApplication();

        self::assertSame($connections, DatabaseComponents::getAll());
    }
}
