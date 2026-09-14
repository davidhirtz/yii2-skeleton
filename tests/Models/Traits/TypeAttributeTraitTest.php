<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class TypeAttributeTraitTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        TypeRecord::$types = null;
        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(TypeRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TypeRecord::tableName())
            ->execute();
    }

    public function testDefinitionsAreIndexedByValueNotByOffset(): void
    {
        self::assertSame(
            [TypeRecord::TYPE_DEFAULT, TypeRecord::TYPE_CHILD],
            array_keys(TypeRecord::getTypeDefinitions()),
        );
    }

    public function testADuplicateValueThrows(): void
    {
        TypeRecord::$types = [
            Type::make(1)->name('One'),
            Type::make(1)->name('Two'),
        ];

        $this->expectException(InvalidConfigException::class);
        TypeRecord::getTypeDefinitions();
    }

    public function testAnArrayItemThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        DefinitionRegistry::get(TypeRecord::class, 'getLegacyTypes', Type::class);
    }

    public function testTheDelegatesReadTheDefinition(): void
    {
        $record = TypeRecord::create();
        $record->type = TypeRecord::TYPE_CHILD;

        self::assertSame('Child', $record->getTypeName());
        self::assertSame('Child', $record->getTypePlural());
        self::assertSame('star', $record->getTypeIcon());
    }

    public function testAnUndeclaredValueHasNoType(): void
    {
        $record = TypeRecord::create();
        $record->type = 999;

        self::assertNull($record->getType());
        self::assertSame('', $record->getTypeName());
        self::assertSame('', $record->getTypePlural());
        self::assertSame('', $record->getTypeIcon());
    }

    /**
     * A form posts a string and PDO answers one for an integer column, so the raw attribute reaches `getType()`
     * before anything typecasts it.
     */
    public function testARawStringValueStillResolves(): void
    {
        $record = TypeRecord::create();
        $record->setAttribute('type', (string)TypeRecord::TYPE_CHILD);

        self::assertSame('Child', $record->getTypeName());
        self::assertInstanceOf(TypeRecordChild::class, TypeRecord::instantiate(['type' => '2']));
    }

    public function testAValueThatIsNotNumericHasNoType(): void
    {
        $record = TypeRecord::create();
        $record->setAttribute('type', 'child');

        self::assertNull($record->getType());
    }

    public function testInstantiateReadsTheModelClass(): void
    {
        self::assertInstanceOf(TypeRecordChild::class, TypeRecord::instantiate(['type' => TypeRecord::TYPE_CHILD]));
        self::assertInstanceOf(TypeRecord::class, TypeRecord::instantiate(['type' => TypeRecord::TYPE_DEFAULT]));
        self::assertInstanceOf(TypeRecord::class, TypeRecord::instantiate([]));
    }

    public function testTypeInstancesCarryTheirTypeAndModelClass(): void
    {
        $instances = TypeRecord::getTypeInstances();

        self::assertSame([TypeRecord::TYPE_DEFAULT, TypeRecord::TYPE_CHILD], array_keys($instances));
        self::assertSame(TypeRecord::TYPE_CHILD, $instances[TypeRecord::TYPE_CHILD]->type);
        self::assertInstanceOf(TypeRecordChild::class, $instances[TypeRecord::TYPE_CHILD]);
    }

    public function testDefinitionsAreCached(): void
    {
        $definitions = TypeRecord::getTypeDefinitions();

        self::assertSame($definitions, TypeRecord::getTypeDefinitions());
        self::assertSame($definitions[TypeRecord::TYPE_DEFAULT], TypeRecord::findType(TypeRecord::TYPE_DEFAULT));
        self::assertSame(TypeRecord::getTypeInstances(), TypeRecord::getTypeInstances());
    }

    public function testTheCacheIsKeyedByLanguage(): void
    {
        $language = Yii::$app->language;
        $english = TypeRecord::getTypeDefinitions();

        try {
            Yii::$app->language = 'de';
            self::assertNotSame($english, TypeRecord::getTypeDefinitions());
        } finally {
            Yii::$app->language = $language;
        }

        self::assertSame($english, TypeRecord::getTypeDefinitions());
    }

    public function testTheCacheIsResetWithTheApplication(): void
    {
        $definitions = TypeRecord::getTypeDefinitions();
        $instances = TypeRecord::getTypeInstances();

        $this->reloadApplication();

        self::assertNotSame($definitions, TypeRecord::getTypeDefinitions());
        self::assertNotSame($instances, TypeRecord::getTypeInstances());
    }
}

/**
 * @property int $type
 */
class TypeRecord extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    final public const int TYPE_CHILD = 2;

    /**
     * @var list<Type>|null
     */
    public static ?array $types = null;

    #[Override]
    public static function getTypes(): array
    {
        return static::$types ?? [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default'),
            Type::make(self::TYPE_CHILD)
                ->name('Child')
                ->icon('star')
                ->modelClass(TypeRecordChild::class),
        ];
    }

    /**
     * The pre-3.0 array declaration, which the registry must refuse.
     *
     * @return list<array<string, string>>
     */
    public static function getLegacyTypes(): array
    {
        return [['name' => 'One']];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_type_record}}';
    }
}

class TypeRecordChild extends TypeRecord
{
}
