<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Data;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class TrailActiveDataProviderTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheRecordsOfAPageAreLoadedTogether(): void
    {
        Trail::deleteAll();

        $ids = User::find()->select('id')->column();
        self::assertGreaterThan(1, count($ids));

        foreach ($ids as $id) {
            $trail = Trail::create();
            $trail->model_class = User::class;
            $trail->model_id = (int)$id;
            self::assertTrue($trail->insert(), print_r($trail->getErrors(), true));
        }

        $trail = Trail::create();
        $trail->model_class = User::class;
        $trail->model_id = 999999;
        self::assertTrue($trail->insert(), print_r($trail->getErrors(), true));

        $provider = new TrailActiveDataProvider();
        $trails = $provider->getModels();

        $count = $this->countQueries(function () use ($trails): void {
            foreach ($trails as $trail) {
                $trail->getModelRecord();
            }
        });

        self::assertSame(0, $count);

        foreach ($trails as $trail) {
            $record = $trail->getModelRecord();
            self::assertInstanceOf(User::class, $record);
            self::assertSame((int)$trail->model_id === 999999, $record->getIsNewRecord());
        }
    }
}
