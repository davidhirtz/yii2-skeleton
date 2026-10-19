<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Models\Actions\DuplicateActiveRecord;
use Hirtz\Skeleton\Test\CustomAttributes\TestRecordCustomAttribute;
use Hirtz\Skeleton\Test\Models\RecordAttributeRecord;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class RecordCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(RecordAttributeRecord::tableName(), [
                'id' => 'pk',
                'name' => 'string null',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(RecordAttributeRecord::tableName())
            ->execute();
    }

    public function testAPopulatedRecordIsReadWithoutAQuery(): void
    {
        $target = $this->createRecord('Å Москва');
        $owner = $this->createRecord('Owner', ['link' => $target->id]);

        $owner->populateCustomAttributeRecord('link', $target);

        $queries = $this->countQueries(fn () => self::assertSame($target, $owner->getCustomAttributeRecord('link')));

        self::assertSame(0, $queries);
    }

    public function testAnUnpopulatedRecordIsLoadedOnceAndAgainAfterTheValueChanged(): void
    {
        $first = $this->createRecord('東京');
        $second = $this->createRecord('🦸 Mu' . "\u{308}" . 'ller');
        $owner = $this->findRecord($this->createRecord('Owner', ['link' => $first->id])->id);

        $records = [];

        $queries = $this->countQueries(function () use ($owner, &$records): void {
            $records[] = $owner->getCustomAttributeRecord('link');
            $records[] = $owner->getCustomAttributeRecord('link');
        });

        self::assertSame(1, $queries);
        self::assertSame($first->id, $records[0]?->getPrimaryKey());
        self::assertSame($records[0], $records[1]);

        $owner->link = $second->id;

        self::assertSame($second->id, $owner->getCustomAttributeRecord('link')?->getPrimaryKey());

        $owner->link = null;

        self::assertNull($owner->getCustomAttributeRecord('link'));
    }

    public function testARecordIsHeldPerLanguage(): void
    {
        $en = $this->createRecord('Å');
        $de = $this->createRecord('Ą');
        $owner = $this->createRecord('Owner', ['hero' => $en->id, 'hero_de' => $de->id]);

        self::assertSame($en->id, $owner->getCustomAttributeRecord('hero')?->getPrimaryKey());
        self::assertSame($de->id, $owner->getCustomAttributeRecord('hero_de')?->getPrimaryKey());
    }

    public function testAGroupItemHoldsItsOwnRecord(): void
    {
        $target = $this->createRecord('Å');
        $owner = $this->createRecord('Owner', ['gallery' => [['image' => $target->id, 'caption' => 'Москва']]]);

        $items = $owner->getCustomAttributeItems('gallery');
        $items[0]->populateCustomAttributeRecord('image', $target);

        $queries = $this->countQueries(function () use ($owner, $target): void {
            $item = $owner->getCustomAttributeItems('gallery')[0];
            self::assertSame($target, $item->getCustomAttributeRecord('image'));
        });

        self::assertSame(0, $queries);
    }

    public function testRecordIdsAreCountedAtEveryDepthAndInEveryLanguage(): void
    {
        $a = $this->createRecord('A');
        $b = $this->createRecord('B');

        $owner = $this->createRecord('Owner', [
            'hero' => $a->id,
            'hero_de' => $b->id,
            'gallery' => [
                ['image' => $a->id, 'caption' => 'Å'],
                ['image' => $b->id],
                ['image' => $a->id],
            ],
            'slides' => [
                ['title' => '東京', 'items' => [['image' => $b->id], ['image' => $b->id]]],
            ],
        ]);

        self::assertSame([
            $a->id => ['gallery.image' => 2, 'hero' => 1],
            $b->id => ['gallery.image' => 1, 'hero_de' => 1, 'slides.items.image' => 2],
        ], $owner->getCustomAttributeRecordIds(TestRecordCustomAttribute::class));
    }

    public function testClearingEmptiesEveryValueAndKeepsTheRows(): void
    {
        $a = $this->createRecord('A');
        $b = $this->createRecord('B');

        $owner = $this->createRecord('Owner', [
            'link' => $b->id,
            'hero' => $a->id,
            'hero_de' => $a->id,
            'gallery' => [['image' => $a->id, 'caption' => 'Å'], ['image' => $b->id]],
            'slides' => [['title' => '東京', 'items' => [['image' => $a->id]]]],
        ]);

        self::assertTrue($owner->clearCustomAttributeRecordId(TestRecordCustomAttribute::class, $a->id));
        self::assertFalse($owner->clearCustomAttributeRecordId(TestRecordCustomAttribute::class, $a->id));

        self::assertSame($b->id, $owner->link);
        self::assertNull($owner->hero);
        self::assertNull($owner->hero_de);
        self::assertSame([['caption' => 'Å'], ['image' => $b->id]], $owner->gallery);
        self::assertSame([['title' => '東京', 'items' => [[]]]], $owner->slides);
        self::assertSame([$b->id], array_keys($owner->getCustomAttributeRecordIds(TestRecordCustomAttribute::class)));
    }

    public function testAStoredValueThatNoLongerResolvesBlocksNoUnrelatedSave(): void
    {
        $owner = $this->createRecord('Owner');
        $this->storeValues($owner, ['link' => 9999, 'gallery' => [['image' => 9998], ['image' => 9999]]]);

        $owner = $this->findRecord($owner->id);
        $owner->name = 'Renamed';
        $owner->gallery = array_reverse((array)$owner->gallery);

        self::assertTrue($owner->save(), implode(' ', $owner->getErrorSummary(true)));
    }

    public function testAChangedValueHasToResolve(): void
    {
        $owner = $this->createRecord('Owner');
        $owner->link = 9999;
        $owner->gallery = [['image' => 9998]];

        self::assertFalse($owner->save());
        self::assertTrue($owner->hasErrors('link'));
        self::assertTrue($owner->hasErrors('gallery.0.image'));
    }

    public function testADuplicateKeepsAValueThatNoLongerResolves(): void
    {
        $owner = $this->createRecord('Owner');
        $this->storeValues($owner, ['link' => 9999, 'gallery' => [['image' => 9998]]]);

        $duplicate = DuplicateActiveRecord::create(['model' => $this->findRecord($owner->id)]);
        self::assertInstanceOf(RecordAttributeRecord::class, $duplicate);

        self::assertFalse($duplicate->getIsNewRecord(), implode(' ', $duplicate->getErrorSummary(true)));
        self::assertSame(9999, $duplicate->link);
    }

    public function testTheTrailValueIsTheRecordsLabel(): void
    {
        $target = $this->createRecord('Å Москва 東京');
        $owner = $this->createRecord('Owner');
        $definition = $owner->getCustomAttribute('link');

        self::assertNotNull($definition);
        self::assertSame('Å Москва 東京', $definition->formatValue($owner, $target->id));
        self::assertSame('#9999', $definition->formatValue($owner, 9999));
        self::assertNull($definition->formatValue($owner, null));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createRecord(string $name, array $values = []): RecordAttributeRecord
    {
        $record = RecordAttributeRecord::create();
        $record->name = $name;
        $record->setAttributes($values, false);

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));

        return $record;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function storeValues(RecordAttributeRecord $record, array $values): void
    {
        $record->updateAttributes(['custom_attributes' => $values]);
    }

    private function findRecord(int $id): RecordAttributeRecord
    {
        return RecordAttributeRecord::find()->where(['id' => $id])->one() ?? self::fail('The record was not found.');
    }
}
