<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Definitions;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Definitions\Definitions;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class DefinitionsTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        $this->getDefinitions()->types = [];
        parent::tearDown();
    }

    public function testTheConfiguredTypesReplaceTheDeclaredOnes(): void
    {
        $this->setTypes(static fn (): array => [
            Type::make(ConfiguredTypeRecord::TYPE_DEFAULT)->name('Configured'),
            Type::make(7)->name('Seven')->icon('star'),
        ]);

        $definitions = ConfiguredTypeRecord::getTypeDefinitions();

        self::assertSame([ConfiguredTypeRecord::TYPE_DEFAULT, 7], array_keys($definitions));
        self::assertSame('Configured', $definitions[ConfiguredTypeRecord::TYPE_DEFAULT]->getName());
        self::assertSame('star', $definitions[7]->getIcon());
    }

    public function testAModelNobodyConfiguredKeepsItsOwnTypes(): void
    {
        self::assertSame('Declared', ConfiguredTypeRecord::getTypeDefinitions()[1]->getName());
    }

    /**
     * The closure is what keeps the name a `Yii::t()` result rather than one frozen at configuration time, so it is
     * evaluated once per language like every other declaration.
     */
    public function testTheClosureIsEvaluatedPerLanguage(): void
    {
        $this->setTypes(static fn (): array => [
            Type::make(1)->name(Yii::$app->language),
        ]);

        $language = Yii::$app->language;

        try {
            self::assertSame($language, ConfiguredTypeRecord::getTypeDefinitions()[1]->getName());

            Yii::$app->language = 'de';
            self::assertSame('de', ConfiguredTypeRecord::getTypeDefinitions()[1]->getName());
        } finally {
            Yii::$app->language = $language;
        }
    }

    public function testTheConfiguredTypesAreValidatedLikeTheDeclaredOnes(): void
    {
        $this->setTypes(static fn (): array => [
            Type::make(1)->name('One'),
            Type::make(1)->name('Two'),
        ]);

        $this->expectException(InvalidConfigException::class);
        ConfiguredTypeRecord::getTypeDefinitions();
    }

    public function testAModelWithoutTypesThrows(): void
    {
        $this->expectException(InvalidConfigException::class);

        Yii::createObject([
            'class' => Definitions::class,
            'types' => [self::class => static fn (): array => []],
        ]);
    }

    private function setTypes(callable $types): void
    {
        $this->getDefinitions()->types = [ConfiguredTypeRecord::class => $types];
    }

    private function getDefinitions(): Definitions
    {
        /** @var Definitions $definitions */
        $definitions = Yii::$app->get('definitions');
        return $definitions;
    }
}

/**
 * @property int $type
 */
class ConfiguredTypeRecord extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    #[Override]
    public static function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)->name('Declared'),
        ];
    }
}
