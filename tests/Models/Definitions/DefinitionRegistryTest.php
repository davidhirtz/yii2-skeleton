<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Definitions;

use Closure;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Traits\DraftStatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class DefinitionRegistryTest extends TestCase
{
    /**
     * `ActiveRecord::__set()` asks `hasAttribute()` before a setter, so a model `Yii::configure()` sets needs its
     * table.
     */
    #[Override]
    protected function setUpSchema(): void
    {
        foreach ([ConfiguredRecord::tableName(), DeclaredRecord::tableName(), DraftRecord::tableName()] as $table) {
            Yii::$app->getDb()->createCommand()
                ->createTable($table, [
                    'id' => 'pk',
                    'status' => 'integer not null default 1',
                    'type' => 'integer not null default 1',
                ])
                ->execute();
        }
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        foreach ([ConfiguredRecord::tableName(), DeclaredRecord::tableName(), DraftRecord::tableName()] as $table) {
            Yii::$app->getDb()->createCommand()->dropTable($table)->execute();
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(ConfiguredRecord::class);
        Yii::$container->clear(DeclaredRecord::class);
        Yii::$container->clear(DraftRecord::class);
        Yii::$container->clear(User::class);

        parent::tearDown();
    }

    public function testTheConfiguredTypesReplaceTheDeclaredOnes(): void
    {
        $this->configure(['types' => static fn (): array => [
            Type::make(ConfiguredRecord::TYPE_DEFAULT)->name('Configured'),
            Type::make(7)->name('Seven')->icon('star'),
        ]]);

        $definitions = ConfiguredRecord::getTypeDefinitions();

        self::assertSame([ConfiguredRecord::TYPE_DEFAULT, 7], array_keys($definitions));
        self::assertSame('Configured', $definitions[ConfiguredRecord::TYPE_DEFAULT]->getName());
        self::assertSame('star', $definitions[7]->getIcon());
    }

    public function testTheConfiguredStatusesReplaceTheDeclaredOnes(): void
    {
        $this->configure(['statuses' => static fn (): array => [
            Status::make(ConfiguredRecord::STATUS_ENABLED)->name('Live')->icon('globe'),
        ]]);

        $definitions = ConfiguredRecord::getStatusDefinitions();

        self::assertSame([ConfiguredRecord::STATUS_ENABLED], array_keys($definitions));
        self::assertSame('Live', $definitions[ConfiguredRecord::STATUS_ENABLED]->getName());
    }

    /**
     * The draft trait and the user declare statuses of their own, which the configuration still replaces.
     */
    public function testTheConfiguredStatusesReplaceTheShippedDeclarations(): void
    {
        self::assertCount(3, DraftRecord::getStatusDefinitions());

        foreach ([DraftRecord::class, User::class] as $class) {
            Yii::$container->set($class, ['statuses' => static fn (): array => [
                Status::make(StatusAttributeInterface::STATUS_DISABLED)->name('Off'),
            ]]);
        }

        DefinitionRegistry::reset();

        self::assertSame('Off', DraftRecord::getStatusDefinitions()[DraftRecord::STATUS_DISABLED]->getName());
        self::assertCount(1, DraftRecord::getStatusDefinitions());
        self::assertCount(1, User::getStatusDefinitions());
    }

    public function testAModelNobodyConfiguredKeepsItsOwnDefinitions(): void
    {
        self::assertSame('Default', ConfiguredRecord::getTypeDefinitions()[1]->getName());
        self::assertCount(2, ConfiguredRecord::getStatusDefinitions());
    }

    /**
     * Only the closure keeps a name a `Yii::t()` result per language rather than one frozen at configuration time.
     */
    public function testTheClosureIsEvaluatedPerLanguage(): void
    {
        $this->configure(['types' => static fn (): array => [
            Type::make(1)->name(Yii::$app->language),
        ]]);

        $language = Yii::$app->language;

        try {
            self::assertSame($language, ConfiguredRecord::getTypeDefinitions()[1]->getName());

            Yii::$app->language = 'de';
            self::assertSame('de', ConfiguredRecord::getTypeDefinitions()[1]->getName());
        } finally {
            Yii::$app->language = $language;
        }
    }

    public function testAPlainListIsAccepted(): void
    {
        $this->configure(['types' => [Type::make(1)->name('Literal')]]);

        self::assertSame('Literal', ConfiguredRecord::getTypeDefinitions()[1]->getName());
    }

    public function testADeclaredOverrideWinsOverTheConfiguration(): void
    {
        Yii::$container->set(DeclaredRecord::class, ['types' => static fn (): array => [
            Type::make(1)->name('Configured'),
        ]]);

        self::assertSame('Declared', DeclaredRecord::getTypeDefinitions()[1]->getName());
    }

    public function testTheConfiguredDefinitionsAreValidatedLikeTheDeclaredOnes(): void
    {
        $this->configure(['types' => static fn (): array => [
            Type::make(1)->name('One'),
            Type::make(1)->name('Two'),
        ]]);

        $this->expectException(InvalidConfigException::class);
        ConfiguredRecord::getTypeDefinitions();
    }

    /**
     * @param array<string, Closure|array> $config
     * @param array<string, mixed> $config
     */
    private function configure(array $config): void
    {
        Yii::$container->set(ConfiguredRecord::class, $config);
    }
}

/**
 * @property int $type
 * @property int $status
 */
class ConfiguredRecord extends ActiveRecord implements StatusAttributeInterface, TypeAttributeInterface
{
    use StatusAttributeTrait;
    use TypeAttributeTrait;
}

/**
 * @property int $type
 */
class DeclaredRecord extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    #[Override]
    public function getTypes(): array
    {
        return [Type::make(self::TYPE_DEFAULT)->name('Declared')];
    }
}

/**
 * @property int $status
 */
class DraftRecord extends ActiveRecord implements DraftStatusAttributeInterface
{
    use DraftStatusAttributeTrait;
}
