<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

/**
 * The index rows are read with an ordinary `WHERE`, so these tests stay inside the rolled back transaction; only a
 * `MATCH` would need committed rows.
 */
class SearchBehaviorTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(SearchableActiveRecord::tableName(), [
                'id' => 'pk',
                'name' => 'string null',
                'content' => 'text null',
                'excluded' => 'string null',
                'tenant_id' => 'int unsigned null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(SearchableActiveRecord::tableName())
            ->execute();
    }

    public function testInsertWritesOneDocumentPerLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $record = $this->createRecord();
        self::assertTrue($record->insert());

        $documents = $this->findDocuments($record);

        self::assertCount(2, $documents);
        self::assertSame(['de', 'en-US'], array_map(fn (Search $s): string => $s->language, $documents));

        $document = $documents[1];

        self::assertSame('Müller', $document->title);
        self::assertSame(7, (int)$document->tenant_id);
        self::assertSame(0.5, (float)$document->weight);
        self::assertStringContainsString('Mueller', $document->content);
    }

    public function testUpdateRewritesOnlyWhenASearchAttributeChanged(): void
    {
        $record = $this->createRecord();
        $record->insert();

        $id = $this->findDocuments($record)[0]->id;

        $record->tenant_id = 8;
        self::assertSame(1, $record->update());

        $document = $this->findDocuments($record)[0];

        self::assertNotSame($id, $document->id);
        self::assertSame(8, (int)$document->tenant_id);

        $id = $document->id;

        $record->excluded = 'not indexed';
        self::assertSame(1, $record->update());

        self::assertSame($id, $this->findDocuments($record)[0]->id);
    }

    public function testDeleteRemovesTheDocuments(): void
    {
        $record = $this->createRecord();
        $record->insert();

        self::assertNotEmpty($this->findDocuments($record));

        $record->delete();
        self::assertEmpty($this->findDocuments($record));
    }

    public function testRecordThatIsNotSearchableIsRemoved(): void
    {
        $record = $this->createRecord();
        $record->insert();

        self::assertNotEmpty($this->findDocuments($record));

        $record->isSearchable = false;
        $record->name = 'Schröder';

        self::assertSame(1, $record->update());
        self::assertEmpty($this->findDocuments($record));
    }

    public function testDisabledModuleWritesNothing(): void
    {
        $this->getAdminModule()->enableSearch = false;

        $record = $this->createRecord();
        $record->insert();

        self::assertEmpty($this->findDocuments($record));
    }

    private function createRecord(): SearchableActiveRecord
    {
        $record = SearchableActiveRecord::create();
        $record->name = 'Müller';
        $record->content = '<p>Straße &amp; Weg</p>';
        $record->tenant_id = 7;

        return $record;
    }

    /**
     * @return list<Search>
     */
    private function findDocuments(SearchableActiveRecord $record): array
    {
        return Search::find()
            ->where([
                'model_class' => $record::class,
                'model_id' => $record->id,
            ])
            ->orderBy(['language' => SORT_ASC])
            ->all();
    }

    private function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $content
 * @property string|null $excluded
 * @property int|null $tenant_id
 */
class SearchableActiveRecord extends ActiveRecord implements SearchableInterface
{
    use SearchableTrait;

    public bool $isSearchable = true;

    public function getSearchAttributes(): array
    {
        return ['name', 'content'];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['name', 'content', 'excluded'],
                'safe',
            ],
        ];
    }

    public function getSearchWeight(): float
    {
        return 0.5;
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_searchable}}';
    }
}
