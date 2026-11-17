<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Override;
use Yii;

class GermanSourceLanguageTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config['sourceLanguage'] = 'de';
        $this->config['language'] = 'de';
        $this->reloadApplication();

        Yii::$app->getI18n()->setLanguages(['de', 'en-US']);
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(GermanSourceLanguageRecord::tableName(), [
                'id' => 'pk',
                'name' => 'string null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(GermanSourceLanguageRecord::tableName())
            ->execute();
    }

    public function testTheMessagesAreStillTranslated(): void
    {
        self::assertSame('Schließen', Yii::t('skeleton', 'MODAL_CLOSE_LABEL'));
        self::assertSame('Close', Yii::t('skeleton', 'MODAL_CLOSE_LABEL', [], 'en-US'));
    }

    public function testEnglishIsTheSuffixedAttribute(): void
    {
        $i18n = Yii::$app->getI18n();

        self::assertSame('name', $i18n->getAttributeName('name', 'de'));
        self::assertSame('name_en_us', $i18n->getAttributeName('name', 'en-US'));
        self::assertSame('de', $i18n->getContentLanguage('fr'));
    }

    public function testGermanIsStoredInTheColumnAndEnglishAsATranslation(): void
    {
        $record = new GermanSourceLanguageRecord();
        $record->name = 'Mu' . "\u{308}" . 'ller Å 東京 🚀';
        $record->setAttribute('name_en_us', 'Привет');

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));

        $column = Yii::$app->getDb()->createCommand('SELECT [[name]] FROM ' . GermanSourceLanguageRecord::tableName() . ' WHERE [[id]] = :id', ['id' => $record->id])->queryScalar();
        self::assertSame('Mu' . "\u{308}" . 'ller Å 東京 🚀', $column);

        $translations = Translation::find()
            ->where(['model_class' => GermanSourceLanguageRecord::class, 'model_id' => $record->id])
            ->select(['value', 'language'])
            ->asArray()
            ->all();

        self::assertSame([['value' => 'Привет', 'language' => 'en-US']], $translations);
    }

    public function testTheQueryReadsGermanFromTheColumnAndJoinsEnglish(): void
    {
        $query = GermanSourceLanguageRecord::find();

        self::assertSame(GermanSourceLanguageRecord::tableName() . '.[[name]]', $query->getI18nAttributeName('name', 'de'));
        self::assertStringContainsString('[[value]]', $query->getI18nAttributeName('name', 'en-US'));
        self::assertSame(['en-US'], $this->getLoadedLanguages($query->withTranslations()));
    }

    public function testTheFormShowsGermanAsTheColumn(): void
    {
        $html = Fieldset::make()
            ->model(new GermanSourceLanguageRecord())
            ->rows(['name'])
            ->render();

        self::assertStringContainsString('data-language="de" data-id="germansourcelanguagerecord-name"', $html);
        self::assertStringContainsString('name="GermanSourceLanguageRecord[name_en_us]"', $html);
    }

    public function testARecordServingGermanCopiesNothing(): void
    {
        $record = new GermanSourceLanguageRecord();
        $record->name = 'Ågot';
        $record->setAttribute('name_en_us', 'English');
        $record->fillI18nSourceAttributes();

        self::assertSame('Ågot', $record->name);
    }

    public function testARecordWithoutGermanCopiesItsFirstLanguageIntoTheColumn(): void
    {
        $record = new GermanSourceLanguageRecord();
        $record->i18nLanguages = ['en-US'];
        $record->setAttribute('name_en_us', 'Ågot 東京');

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));
        self::assertSame('Ågot 東京', $record->name);
    }

    /**
     * @param I18nActiveQuery<GermanSourceLanguageRecord> $query
     * @return list<string>
     */
    private function getLoadedLanguages(I18nActiveQuery $query): array
    {
        $languages = (fn (): ?array => $this->translationLanguages)->call($query);
        self::assertIsArray($languages);

        return $languages;
    }
}

/**
 * @property int $id
 * @property string|null $name
 */
class GermanSourceLanguageRecord extends ActiveRecord implements I18nAttributeInterface, TranslationInterface
{
    use I18nAttributesTrait;
    use TranslationTrait;

    /**
     * @var list<string>|null
     */
    public ?array $i18nLanguages = null;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public function getI18nLanguages(): array
    {
        return $this->i18nLanguages ?? Yii::$app->getI18n()->getLanguages();
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
    public function rules(): array
    {
        return $this->getI18nRules([
            [
                ['name'],
                'string',
                'max' => 255,
            ],
        ]);
    }

    #[Override]
    public static function tableName(): string
    {
        return 'german_source_language_record';
    }
}
