<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Test\TestCase;

class TrailTest extends TestCase
{
    public function testTheDefaultTypeIsNotTheCreateType(): void
    {
        self::assertNotSame(Trail::TYPE_CREATE, Trail::TYPE_DEFAULT);
        self::assertArrayHasKey(Trail::TYPE_DEFAULT, Trail::getTypes());
    }

    public function testATrailWithoutATypeIsAPlainMessage(): void
    {
        $trail = Trail::create();
        $trail->message = 'TRAIL_MESSAGE_TEST';

        self::assertTrue($trail->insert());
        self::assertSame(Trail::TYPE_DEFAULT, $trail->type);

        $trail->refresh();
        self::assertSame(Trail::TYPE_DEFAULT, $trail->type);

        self::assertFalse($trail->isCreateType());
        self::assertFalse($trail->hasAttributesEnabled());
        self::assertFalse($trail->hasDataModelEnabled());
    }

    public function testTheColumnDefaultIsThePlainMessageType(): void
    {
        $default = Trail::getTableSchema()->getColumn('type')->defaultValue;
        self::assertSame(Trail::TYPE_DEFAULT, (int)$default);
    }
}
