<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Models\CustomAttributes\DateCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\DateTimeCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Query;

class DateCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(DateRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(DateRecord::tableName())
            ->execute();
    }

    public function testADateTimeIsReadInTheApplicationZoneAndStoredInUtc(): void
    {
        $this->inTimeZone('Europe/Berlin', function (): void {
            $record = DateRecord::create();
            $record->starts_at = '2026-10-09T18:00';

            self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
            self::assertSame('2026-10-09T16:00:00+00:00', $this->getStoredValues($record)['starts_at']);

            $record = DateRecord::findOne($record->id);
            self::assertInstanceOf(DateTime::class, $record?->starts_at);
            self::assertSame('2026-10-09 18:00 +02:00', $record->starts_at->format('Y-m-d H:i P'));
        });
    }

    public function testAStoredDateTimeKeepsItsInstantInAnotherZone(): void
    {
        $record = $this->createRecord(['starts_at' => '2026-10-09T16:00:00+00:00']);

        $this->inTimeZone('America/New_York', function () use ($record): void {
            $record = DateRecord::findOne($record->id);
            self::assertInstanceOf(DateTime::class, $record?->starts_at);
            self::assertSame('2026-10-09 12:00 -04:00', $record->starts_at->format('Y-m-d H:i P'));
        });
    }

    public function testADateIsNeverShifted(): void
    {
        $this->inTimeZone('Pacific/Honolulu', function (): void {
            $record = DateRecord::create();
            $record->day = '2026-10-09';

            self::assertTrue($record->save());
            self::assertSame('2026-10-09', $this->getStoredValues($record)['day']);

            $record = DateRecord::findOne($record->id);
            self::assertInstanceOf(Date::class, $record?->day);
            self::assertSame('2026-10-09', (string)$record->day);
        });

        $this->inTimeZone('Pacific/Kiritimati', function (): void {
            self::assertSame('2026-10-09', (string)DateRecord::find()->one()?->day);
        });
    }

    public function testADateTimeAssignedToADateIsItsDayInTheApplicationZone(): void
    {
        $this->inTimeZone('Europe/Berlin', function (): void {
            $record = DateRecord::create();
            $record->setAttribute('day', new \DateTimeImmutable('2026-10-09 23:30:00', new \DateTimeZone('UTC')));

            self::assertTrue($record->validate());
            self::assertInstanceOf(Date::class, $record->day);
            self::assertSame('2026-10-10', (string)$record->day);
        });
    }

    public function testAnEqualDatePostedBackIsNotAChange(): void
    {
        $record = $this->createRecord(['starts_at' => '2026-10-09T16:00:00+00:00', 'day' => '2026-10-09']);
        $record = DateRecord::findOne($record->id);
        self::assertNotNull($record);

        $record->starts_at = '2026-10-09T16:00';
        $record->day = '2026-10-09';

        self::assertTrue($record->validate());
        self::assertSame([], $record->getDirtyAttributes(['starts_at', 'day']));

        $record->starts_at = '2026-10-09T16:01';
        $record->day = '2026-10-10';

        self::assertTrue($record->validate());
        self::assertSame(['starts_at', 'day'], array_keys($record->getDirtyAttributes(['starts_at', 'day'])));
    }

    public function testAValueThatIsNoDateIsRefusedAndKept(): void
    {
        foreach (['2024-02-31', 'not a date', 'Mu' . "\u{308}" . 'ller', '🗓️'] as $value) {
            $record = DateRecord::create();
            $record->starts_at = $value;
            $record->day = $value;

            self::assertFalse($record->validate(), $value);
            self::assertTrue($record->hasErrors('starts_at'), $value);
            self::assertTrue($record->hasErrors('day'), $value);
            self::assertSame($value, $record->starts_at);
        }
    }

    public function testAnEmptyValueRemovesTheKey(): void
    {
        $record = $this->createRecord(['starts_at' => '2026-10-09T16:00:00+00:00', 'day' => '2026-10-09']);
        $record->starts_at = '';
        $record->day = null;

        self::assertTrue($record->save());
        self::assertNull($record->starts_at);
        self::assertSame([], $this->getStoredValues($record));
    }

    public function testRequired(): void
    {
        $record = RequiredDateRecord::create();
        $record->day = '';

        self::assertFalse($record->validate());
        self::assertTrue($record->hasErrors('day'));
    }

    public function testTheFieldsShowTheValueInTheApplicationZone(): void
    {
        $this->inTimeZone('Europe/Berlin', function (): void {
            $record = $this->createRecord(['starts_at' => '2026-10-09T16:00:00+00:00', 'day' => '2026-10-09']);
            $record = DateRecord::findOne($record->id);
            self::assertNotNull($record);

            $definitions = $record->getCustomAttributeDefinitions();
            $dateTime = (string)$definitions['starts_at']->createField($record)->render();
            $date = (string)$definitions['day']->createField($record)->render();

            self::assertStringContainsString('type="datetime-local"', $dateTime);
            self::assertStringContainsString('value="2026-10-09T18:00"', $dateTime);
            self::assertStringContainsString('type="date"', $date);
            self::assertStringContainsString('value="2026-10-09"', $date);
        });
    }

    public function testTheTrailValueIsFormatted(): void
    {
        $record = DateRecord::create();
        $definitions = $record->getCustomAttributeDefinitions();

        $dateTime = new DateTime('2026-10-09 16:00:00', new \DateTimeZone('UTC'));
        $date = new Date('2026-10-09', new \DateTimeZone('UTC'));

        $expected = Yii::$app->getFormatter()->asDatetime($dateTime, 'medium');
        $trail = json_decode(json_encode($dateTime, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($expected, $definitions['starts_at']->formatValue($record, $trail));
        self::assertSame($expected, $definitions['starts_at']->formatValue($record, $dateTime));
        self::assertSame(Yii::$app->getFormatter()->asDate($date, 'medium'), $definitions['day']->formatValue($record, $date));
        self::assertNull($definitions['day']->formatValue($record, null));
    }

    public function testADateInAGroupRoundTrips(): void
    {
        $this->inTimeZone('Europe/Berlin', function (): void {
            $record = DateRecord::create();
            $record->dates = [['label' => 'Premiere', 'starts_at' => '2026-10-09T20:00']];

            self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
            self::assertSame(
                [['label' => 'Premiere', 'starts_at' => '2026-10-09T18:00:00+00:00']],
                $this->getStoredValues($record)['dates'],
            );

            $record = DateRecord::findOne($record->id);
            self::assertNotNull($record);

            $item = $record->getCustomAttributeItems('dates')[0];
            $startsAt = $item->getAttribute('starts_at');

            self::assertInstanceOf(DateTime::class, $startsAt);
            self::assertSame('2026-10-09 20:00', $startsAt->format('Y-m-d H:i'));
            self::assertStringContainsString(
                'value="2026-10-09T20:00"',
                (string)$item->getCustomAttribute('starts_at')?->createField($item)->render(),
            );
        });
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createRecord(array $values): DateRecord
    {
        $record = DateRecord::create();
        $record->setAttributes($values, false);

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    private function getStoredValues(DateRecord $record): array
    {
        $json = (new Query())
            ->select('custom_attributes')
            ->from(DateRecord::tableName())
            ->where(['id' => $record->id])
            ->scalar();

        return is_string($json) ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    private function inTimeZone(string $timeZone, callable $callback): void
    {
        $previous = Yii::$app->getTimeZone();
        Yii::$app->setTimeZone($timeZone);

        try {
            $callback();
        } finally {
            Yii::$app->setTimeZone($previous);
        }
    }
}

/**
 * @property int $id
 * @property DateTime|string|null $starts_at
 * @property Date|string|null $day
 * @property list<array<string, mixed>>|null $dates
 */
class DateRecord extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            DateTimeCustomAttribute::make('starts_at'),
            DateCustomAttribute::make('day'),
            GroupCustomAttribute::make('dates')
                ->multiple()
                ->attributes([
                    TextCustomAttribute::make('label'),
                    DateTimeCustomAttribute::make('starts_at'),
                ]),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'date_custom_attribute_test';
    }
}

class RequiredDateRecord extends DateRecord
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [DateCustomAttribute::make('day')->required()];
    }
}
