<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;

/**
 * The admin only ever filtered an unavailable type out of the select, so a hand-posted value reached the database.
 */
class DynamicRangeValidatorTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        DynamicRangeRecord::$types = null;
        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(DynamicRangeRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(DynamicRangeRecord::tableName())
            ->execute();
    }

    public function testEveryDeclaredTypeIsInRangeByDefault(): void
    {
        $record = DynamicRangeRecord::create();
        $record->type = DynamicRangeRecord::TYPE_RESTRICTED;

        self::assertTrue($record->validate(), implode(' ', $record->getErrorSummary(true)));
    }

    public function testAnUnavailableTypeIsRefused(): void
    {
        $this->setRestricted(false);

        $record = DynamicRangeRecord::create();
        $record->type = DynamicRangeRecord::TYPE_RESTRICTED;

        self::assertFalse($record->validate());
        self::assertArrayHasKey('type', $record->getErrors());
    }

    public function testAnAvailableTypeIsAccepted(): void
    {
        $this->setRestricted(true);

        $record = DynamicRangeRecord::create();
        $record->type = DynamicRangeRecord::TYPE_RESTRICTED;

        self::assertTrue($record->validate(), implode(' ', $record->getErrorSummary(true)));
    }

    /**
     * A rule that stops matching must not lock a record that already holds the type out of every later save.
     */
    public function testTheStoredTypeStaysValidAfterTheRuleStopsMatching(): void
    {
        $this->setRestricted(true);

        $record = DynamicRangeRecord::create();
        $record->type = DynamicRangeRecord::TYPE_RESTRICTED;

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));

        $this->setRestricted(false);

        self::assertTrue($record->validate(), implode(' ', $record->getErrorSummary(true)));
        self::assertNotFalse($record->update(), implode(' ', $record->getErrorSummary(true)));
    }

    /**
     * The exemption is the stored value alone — a record cannot be moved onto another unavailable type.
     */
    public function testAStoredRecordStillCannotTakeAnotherUnavailableType(): void
    {
        $record = DynamicRangeRecord::create();
        $record->type = DynamicRangeRecord::TYPE_DEFAULT;

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));

        $this->setRestricted(false);
        $record->type = DynamicRangeRecord::TYPE_RESTRICTED;

        self::assertFalse($record->validate());
    }

    /**
     * A `get<Plural>()` returning a plain `value => label` map holds no definitions to ask, so it passes through.
     */
    public function testAPlainRangeIsUnaffected(): void
    {
        $range = (new DynamicRangeValidator())->getDynamicRange(DynamicRangeRecord::create(), 'colour');

        self::assertSame(['red', 'blue'], $range);
    }

    /**
     * The definitions are cached per class, so replacing the declaration after one has resolved — which every
     * insert and validate in these tests does — only takes effect once the registry is reset.
     */
    private function setRestricted(bool $available): void
    {
        DefinitionRegistry::resetClass(DynamicRangeRecord::class);

        DynamicRangeRecord::$types = [
            Type::make(DynamicRangeRecord::TYPE_DEFAULT)
                ->name('Default'),
            Type::make(DynamicRangeRecord::TYPE_RESTRICTED)
                ->name('Restricted')
                ->available($available),
        ];
    }
}

/**
 * @property int $type
 */
class DynamicRangeRecord extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    final public const int TYPE_RESTRICTED = 2;

    /**
     * @var list<Type>|null
     */
    public static ?array $types = null;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['type'],
                DynamicRangeValidator::class,
                'skipOnEmpty' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColours(): array
    {
        return ['red' => 'Red', 'blue' => 'Blue'];
    }

    #[Override]
    public function getTypes(): array
    {
        return static::$types ?? [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default'),
            Type::make(self::TYPE_RESTRICTED)
                ->name('Restricted'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%dynamic_range_record}}';
    }
}
