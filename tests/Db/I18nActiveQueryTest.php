<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class I18nActiveQueryTest extends TestCase
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
            ->createTable(I18nActiveRecord::tableName(), [
                'id' => 'pk',
                'content' => 'string null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(I18nActiveRecord::tableName())
            ->execute();
    }

    public function testI18nAttributeName(): void
    {
        $tableName = I18nActiveRecord::tableName();

        self::assertEquals("$tableName.[[id]]", I18nActiveRecord::find()->getI18nAttributeName('id'));
        self::assertEquals("$tableName.[[content]]", I18nActiveRecord::find()->getI18nAttributeName('content'));

        self::assertEquals(
            '[[t_content_de]].[[value]]',
            I18nActiveRecord::find()->getI18nAttributeName('content', 'de')
        );

        self::assertEquals(
            "COALESCE(NULLIF([[t_content_de]].[[value]], ''), $tableName.[[content]])",
            I18nActiveRecord::find()->getI18nAttributeName('content', 'de', fallback: true)
        );
    }

    public function testTranslationIsJoinedOnce(): void
    {
        $query = I18nActiveRecord::find();

        self::assertEquals('[[t_content_de]].[[value]]', $query->getI18nAttributeName('content', 'de'));
        self::assertEquals('t_content_de', $query->joinTranslation('content', 'de'));

        self::assertCount(1, $query->join);
    }

    public function testOrderByTranslatedAttributeFallsBackToSourceColumn(): void
    {
        $db = Yii::$app->getDb();
        $tableName = $db->quoteTableName($db->getSchema()->getRawTableName(I18nActiveRecord::tableName()));

        $sql = I18nActiveRecord::find()
            ->orderBy(['content_de' => SORT_ASC])
            ->createCommand()
            ->sql;

        self::assertStringContainsString(
            "ORDER BY COALESCE(NULLIF(`t_content_de`.`value`, ''), $tableName.`content`)",
            $sql
        );
    }

    public function testWithTranslationsLoadsEveryRowWithOneQuery(): void
    {
        foreach (['One', 'Two', 'Three'] as $index => $content) {
            $record = new I18nActiveRecord();
            $record->content = $content;
            $record->content_de = "$content DE";

            self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
            self::assertSame($index + 1, (int)$record->id);
        }

        $records = [];

        $queries = $this->countQueries(function () use (&$records): void {
            $records = I18nActiveRecord::find()
                ->withTranslations('de')
                ->orderBy(['id' => SORT_ASC])
                ->all();
        });

        self::assertCount(3, $records);
        self::assertSame(2, $queries, 'Eager loading the translations took more than one extra query.');

        $queries = $this->countQueries(function () use ($records): void {
            foreach ($records as $record) {
                self::assertSame("$record->content DE", $record->content_de);
            }
        });

        self::assertSame(0, $queries, 'Reading an eager loaded translation queried the database.');
    }

    public function testAllEagerLoadsEveryLanguageByDefault(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);

        foreach (['One', 'Two'] as $content) {
            $record = new I18nActiveRecord();
            $record->content = $content;
            $record->content_de = "$content DE";
            $record->content_fr = "$content FR";

            self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));
        }

        $records = [];

        $queries = $this->countQueries(function () use (&$records): void {
            $records = I18nActiveRecord::find()->orderBy(['id' => SORT_ASC])->all();
        });

        self::assertSame(2, $queries, 'A list did not eager load its translations in one extra query.');

        $queries = $this->countQueries(function () use ($records): void {
            foreach ($records as $record) {
                self::assertSame("$record->content DE", $record->content_de);
                self::assertSame("$record->content FR", $record->content_fr);
            }
        });

        self::assertSame(0, $queries, 'Reading a translation of a listed record queried the database.');
    }

    public function testWithoutTranslationsLoadsLazily(): void
    {
        $record = new I18nActiveRecord();
        $record->content = 'One';
        $record->content_de = 'Eins';

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));

        $records = [];

        $queries = $this->countQueries(function () use (&$records): void {
            $records = I18nActiveRecord::find()->withoutTranslations()->all();
        });

        self::assertSame(1, $queries);
        self::assertSame(1, $this->countQueries(fn () => self::assertSame('Eins', $records[0]->content_de)));
    }

    public function testTranslationIsLoadedLazilyPerRecord(): void
    {
        $record = new I18nActiveRecord();
        $record->content = 'One';
        $record->content_de = 'Eins';

        self::assertTrue($record->save(), implode(' ', $record->getErrorSummary(true)));

        $loaded = I18nActiveRecord::findOne($record->id);

        $queries = $this->countQueries(function () use ($loaded): void {
            self::assertSame('Eins', $loaded->content_de);
            self::assertSame('Eins', $loaded->content_de);
        });

        self::assertSame(1, $queries);
        self::assertSame('Eins', $loaded->getOldAttribute('content_de'));
    }
}

/**
 * @property int $id
 * @property string|null $content
 * @property string|null $content_de
 * @property string|null $content_fr
 */
class I18nActiveRecord extends ActiveRecord implements TranslationInterface
{
    use I18nAttributesTrait;
    use TranslationTrait;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['content'];
        parent::init();
    }

    #[Override]
    public function rules(): array
    {
        return $this->getI18nRules([
            [
                ['content'],
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
        return '{{%i18n_test}}';
    }
}
