<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Behaviors;

use DateTimeImmutable;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Override;
use Yii;
use yii\db\Query;

class AttributeTypecastBehaviorDateTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->setTimeZone('Europe/Berlin');
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$app->setTimeZone('UTC');
        AttributeTypecastBehavior::clearAutoDetectedAttributeTypes();

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(AttributeTypecastDateActiveRecord::tableName(), [
                'id' => 'pk',
                'published_at' => 'datetime null',
                'day' => 'date null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(AttributeTypecastDateActiveRecord::tableName())
            ->execute();
    }

    public function testTheColumnsNameTheirType(): void
    {
        $behavior = new AttributeTypecastBehavior();
        $behavior->attach(new AttributeTypecastDateActiveRecord());

        self::assertSame([
            'published_at' => AttributeTypecastBehavior::TYPE_DATETIME,
            'day' => AttributeTypecastBehavior::TYPE_DATE,
        ], $behavior->getDateAttributes());
    }

    public function testAFoundRecordHoldsDatesInTheApplicationTimeZone(): void
    {
        $model = $this->findRecord('2024-06-15 12:30:00', '2024-06-15');

        self::assertInstanceOf(DateTime::class, $model->published_at);
        self::assertSame('2024-06-15 14:30', $model->published_at->format('Y-m-d H:i'));
        self::assertSame('Europe/Berlin', $model->published_at->getTimezone()->getName());
        self::assertSame('2024-06-15 12:30:00', (string)$model->published_at);

        self::assertInstanceOf(Date::class, $model->day);
        self::assertSame('2024-06-15 00:00', $model->day->format('Y-m-d H:i'));
        self::assertSame('2024-06-15', (string)$model->day);

        self::assertSame($model->published_at, $model->getOldAttribute('published_at'));
        self::assertSame([], $model->getDirtyAttributes());
    }

    public function testANullOrUnselectedDateStaysNull(): void
    {
        $model = $this->findRecord(null, null);

        self::assertNull($model->published_at);
        self::assertNull($model->day);

        $model = AttributeTypecastDateActiveRecord::find()->select(['id'])->one();
        self::assertNotNull($model);
        self::assertSame(['id'], array_keys($model->getOldAttributes()));
    }

    public function testAModifiedDateIsAChangeAndLeavesTheOldValueAlone(): void
    {
        $model = $this->findRecord('2024-06-15 12:30:00', '2024-06-15');
        $publishedAt = $model->published_at;
        self::assertInstanceOf(DateTime::class, $publishedAt);

        $model->published_at = $publishedAt->modify('+1 day');

        self::assertSame('2024-06-15 12:30:00', (string)$model->getOldAttribute('published_at'));
        self::assertTrue($model->isAttributeChanged('published_at'));
        self::assertTrue($model->save());

        self::assertSame('2024-06-16 12:30:00', $this->queryColumn('published_at'));
    }

    public function testAPostedDateIsReadInTheApplicationTimeZone(): void
    {
        $model = new AttributeTypecastDateActiveRecord();
        $model->load(['published_at' => '2024-06-15T14:30', 'day' => '2024-06-15'], '');

        self::assertInstanceOf(DateTime::class, $model->published_at);
        self::assertSame('2024-06-15 12:30:00', (string)$model->published_at);
        self::assertInstanceOf(Date::class, $model->day);
        self::assertSame('2024-06-15', (string)$model->day);

        self::assertTrue($model->save());
        self::assertSame('2024-06-15 12:30:00', $this->queryColumn('published_at'));
        self::assertSame('2024-06-15', $this->queryColumn('day'));
    }

    public function testPostingTheStoredDateIsNotAChange(): void
    {
        $model = $this->findRecord('2024-06-15 12:30:00', '2024-06-15');
        $publishedAt = $model->published_at;
        $day = $model->day;

        $model->load(['published_at' => '2024-06-15T14:30', 'day' => '2024-06-15'], '');

        self::assertSame($publishedAt, $model->published_at);
        self::assertSame($day, $model->day);
        self::assertSame([], $model->getDirtyAttributes());
    }

    public function testAnotherDateIsConverted(): void
    {
        $model = new AttributeTypecastDateActiveRecord();

        $model->published_at = new \DateTime('2024-06-15 14:30:00', new \DateTimeZone('Europe/London'));
        $model->day = new DateTime('2024-06-15 23:30:00');
        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertInstanceOf(DateTime::class, $model->published_at);
        self::assertSame('2024-06-15 13:30:00', (string)$model->published_at);
        self::assertSame('Europe/Berlin', $model->published_at->getTimezone()->getName());
        self::assertInstanceOf(Date::class, $model->day);
        self::assertSame('2024-06-15', (string)$model->day);

        $model->published_at = 1718454600;
        $model->day = new DateTimeImmutable('2024-06-15 23:30:00', new \DateTimeZone('UTC'));
        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertInstanceOf(DateTime::class, $model->published_at);
        self::assertSame('2024-06-15 12:30:00', (string)$model->published_at);
        self::assertInstanceOf(Date::class, $model->day);
        self::assertSame('2024-06-16', (string)$model->day);
    }

    public function testAnUnreadableDateIsLeftForTheValidator(): void
    {
        $model = new AttributeTypecastDateActiveRecord();

        foreach (['tomorrow-ish', '2024-02-31', ' '] as $value) {
            $model->load(['published_at' => $value], '');

            self::assertSame($value, $model->published_at);
            self::assertFalse($model->validate());
            self::assertTrue($model->hasErrors('published_at'));
        }
    }

    public function testAnEmptyDateOfANullableColumnIsNull(): void
    {
        $model = $this->findRecord('2024-06-15 12:30:00', '2024-06-15');
        $model->load(['published_at' => '', 'day' => ''], '');

        self::assertNull($model->published_at);
        self::assertNull($model->day);
        self::assertTrue($model->save());
        self::assertNull($this->queryColumn('published_at'));
    }

    private function findRecord(?string $publishedAt, ?string $day): AttributeTypecastDateActiveRecord
    {
        Yii::$app->getDb()->createCommand()
            ->insert(AttributeTypecastDateActiveRecord::tableName(), [
                'published_at' => $publishedAt,
                'day' => $day,
            ])
            ->execute();

        $model = AttributeTypecastDateActiveRecord::find()->one();
        self::assertNotNull($model);

        return $model;
    }

    private function queryColumn(string $column): mixed
    {
        return (new Query())
            ->select($column)
            ->from(AttributeTypecastDateActiveRecord::tableName())
            ->scalar();
    }
}

/**
 * @property int $id
 * @property DateTime|int|string|\DateTimeInterface|null $published_at
 * @property Date|string|\DateTimeInterface|null $day
 */
class AttributeTypecastDateActiveRecord extends ActiveRecord
{
    #[Override]
    public function rules(): array
    {
        return [
            [
                ['published_at', 'day'],
                DateTimeValidator::class,
            ],
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'test_attribute_typecast_date';
    }

    public function getAttributeTypecastBehavior(): AttributeTypecastBehavior
    {
        /** @var AttributeTypecastBehavior $behavior */
        $behavior = $this->getBehavior('AttributeTypecastBehavior');
        return $behavior;
    }
}
