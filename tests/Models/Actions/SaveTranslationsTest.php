<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Actions;

use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class SaveTranslationsTest extends TestCase
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
            ->createTable(TranslatedActiveRecord::tableName(), [
                'id' => 'pk',
                'name' => 'string null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TranslatedActiveRecord::tableName())
            ->execute();
    }

    public function testInsertWritesTranslationsAndReturnsTheChanges(): void
    {
        $model = new TranslatedActiveRecord();
        $model->name = 'Name';

        self::assertTrue($model->insert());
        self::assertNull($this->findTranslation($model));

        $model->name_de = 'Name DE';

        self::assertSame(['name_de' => null], $model->saveVirtualAttributes());
        self::assertSame('Name DE', $this->findTranslation($model)?->value);
    }

    public function testInsertTrailLogsTheTranslatedAttribute(): void
    {
        $model = $this->createRecord();

        self::assertSame('Name DE', $this->findTranslation($model)?->value);
        self::assertSame('Name DE', $this->findLatestTrail($model)->data['name_de']);
    }

    public function testUpdateReturnsTheStoredValue(): void
    {
        $model = $this->createRecord();
        $loaded = TranslatedActiveRecord::findOne($model->id);

        // `setAttribute()` bypasses the lazy load, so the old attribute is unknown and only the stored record knows.
        $loaded->setAttribute('name_de', 'Name DE Updated');

        self::assertSame(['name_de' => 'Name DE'], $loaded->saveVirtualAttributes());
        self::assertSame('Name DE Updated', $this->findTranslation($model)?->value);
    }

    /**
     * The admin update path writes via `load()` before anything is read.
     */
    public function testWritingBeforeReadingKeepsTheOldValue(): void
    {
        $model = $this->createRecord();
        $loaded = TranslatedActiveRecord::findOne($model->id);

        $loaded->name_de = 'Name DE Updated';

        self::assertSame('Name DE', $loaded->getOldAttribute('name_de'));
        self::assertTrue($loaded->isAttributeChanged('name_de'));

        self::assertSame(1, $loaded->update());
        self::assertSame(['Name DE', 'Name DE Updated'], $this->findLatestTrail($loaded)->data['name_de']);
    }

    public function testUnchangedValueIsNotWritten(): void
    {
        $model = $this->createRecord();

        $queries = $this->countQueries(function () use ($model): void {
            $model->name_de = 'Name DE';
            self::assertSame([], $model->saveVirtualAttributes());
        });

        self::assertSame(0, $queries);
    }

    public function testEmptyValueDeletesTheRecord(): void
    {
        $model = $this->createRecord();
        $model->name_de = '';

        self::assertSame(['name_de' => 'Name DE'], $model->saveVirtualAttributes());
        self::assertNull($this->findTranslation($model));
    }

    public function testDeleteRemovesEveryRecord(): void
    {
        $model = $this->createRecord();

        self::assertSame(1, $model->delete());
        self::assertNull($this->findTranslation($model));
    }

    public function testTrailLogsTheTranslatedAttribute(): void
    {
        $model = $this->createRecord();

        $model->name_de = 'Name DE Updated';
        self::assertSame(1, $model->update());

        $trail = $this->findLatestTrail($model);

        self::assertSame(Trail::TYPE_UPDATE, $trail->type);
        self::assertSame(['Name DE', 'Name DE Updated'], $trail->data['name_de']);
    }

    protected function createRecord(): TranslatedActiveRecord
    {
        $model = new TranslatedActiveRecord();
        $model->name = 'Name';
        $model->name_de = 'Name DE';

        self::assertTrue($model->save(), implode(' ', $model->getErrorSummary(true)));

        return $model;
    }

    protected function findLatestTrail(TranslatedActiveRecord $model): Trail
    {
        return Trail::find()
            ->where([
                'model_class' => $model::class,
                'model_id' => $model->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    protected function findTranslation(TranslatedActiveRecord $model): ?Translation
    {
        return Translation::find()
            ->whereModel(TranslatedActiveRecord::class, (int)$model->id)
            ->whereLanguage('de')
            ->whereAttribute('name')
            ->one();
    }
}

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $name_de
 */
class TranslatedActiveRecord extends ActiveRecord implements TrailModelInterface, TranslationInterface
{
    use I18nAttributesTrait;
    use TrailModelTrait;
    use TranslationTrait;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return $this->getI18nRules([
            [
                ['name'],
                'string',
            ],
        ]);
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    /**
     * @return I18nActiveQuery<static>
     */
    #[Override]
    public static function find(): I18nActiveQuery
    {
        return Yii::createObject(I18nActiveQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return 'translated_test';
    }
}
