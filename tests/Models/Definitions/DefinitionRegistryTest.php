<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Definitions;

use Closure;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\InvalidConfigException;

/**
 * The declaration is an instance method, so an installation names a model's types and statuses in the container
 * instead of subclassing it — which is all a small project needs to be fully configured.
 */
class DefinitionRegistryTest extends TestCase
{
    /**
     * The configured value reaches the model through `Yii::configure()`, and `ActiveRecord::__set()` asks
     * `hasAttribute()` before it looks for a setter — so a model configured this way needs its table.
     */
    #[Override]
    protected function setUpSchema(): void
    {
        foreach ([ConfiguredRecord::tableName(), DeclaredRecord::tableName()] as $table) {
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
        foreach ([ConfiguredRecord::tableName(), DeclaredRecord::tableName()] as $table) {
            Yii::$app->getDb()->createCommand()->dropTable($table)->execute();
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(ConfiguredRecord::class);
        Yii::$container->clear(DeclaredRecord::class);

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

    public function testAModelNobodyConfiguredKeepsItsOwnDefinitions(): void
    {
        self::assertSame('Default', ConfiguredRecord::getTypeDefinitions()[1]->getName());
        self::assertCount(2, ConfiguredRecord::getStatusDefinitions());
    }

    /**
     * A plain list is accepted, but it is the closure that keeps a name a `Yii::t()` result rather than one frozen
     * at configuration time — so the definitions resolve once per language, as a declared list does.
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

    /**
     * A class that declares its own types owns them: the override never reads what the container configured.
     */
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
