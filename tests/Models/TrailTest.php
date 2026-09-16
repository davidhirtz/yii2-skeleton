<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Types\TrailType;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class TrailTest extends TestCase
{
    public function testTheDefaultTypeIsNotTheCreateType(): void
    {
        self::assertNotSame(Trail::TYPE_CREATE, Trail::TYPE_DEFAULT);
        self::assertArrayHasKey(Trail::TYPE_DEFAULT, Trail::getTypeDefinitions());
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

    /**
     * A trail is built from a type the caller already knows, so the class that type names is what it has to be.
     */
    public function testTheTypeDecidesTheTrailClass(): void
    {
        Yii::$container->set(Trail::class, TrailTestTrail::class);
        DefinitionRegistry::resetClass(Trail::class);

        try {
            self::assertInstanceOf(TrailTestOrderTrail::class, Trail::createOrderTrail(null));
            self::assertInstanceOf(TrailTestTrail::class, Trail::instantiate(['type' => Trail::TYPE_DEFAULT]));
        } finally {
            Yii::$container->clear(Trail::class);
            DefinitionRegistry::resetClass(Trail::class);
        }
    }
}

class TrailTestTrail extends Trail
{
    #[Override]
    public function getTypes(): array
    {
        return [
            TrailType::make(self::TYPE_DEFAULT)
                ->name('Message'),
            TrailType::make(self::TYPE_ORDER)
                ->name('Order')
                ->modelClass(TrailTestOrderTrail::class),
        ];
    }
}

class TrailTestOrderTrail extends TrailTestTrail
{
}
