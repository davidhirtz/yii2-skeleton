<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\I18nActiveQuery;
use davidhirtz\yii2\skeleton\models\traits\I18nAttributesTrait;
use Yii;
use yii\base\Model;

class I18nTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'en-US';

        parent::_before();
    }

    public function testTranslatedAttributes(): void
    {
        $model = new class() extends ActiveRecord {
            use I18nAttributesTrait;

            public ?string $translated = 'translated';
            public ?string $translated_de = 'translated_de';
            public ?string $untranslated = 'untranslated';

            public function init(): void
            {
                $this->i18nAttributes = ['translated'];
                parent::init();
            }
        };

        $this->assertTrue($model->isI18nAttribute('translated'));
        $this->assertFalse($model->isI18nAttribute('untranslated'));

        $this->assertEquals($model->translated, $model->getI18nAttribute('translated'));
        $this->assertEquals($model->untranslated, $model->getI18nAttribute('untranslated'));
        $this->assertEquals($model->translated_de, $model->getI18nAttribute('translated', 'de'));

        $expected = ['en-US' => 'translated', 'de' => 'translated_de'];
        $this->assertEquals($expected, $model->getI18nAttributeNames('translated'));

        $this->assertEquals(['translated', 'translated_de', 'untranslated'], $model->getI18nAttributesNames(['translated', 'untranslated']));
        $this->assertEquals(['translated_de', 'untranslated'], $model->getI18nAttributesNames(['translated', 'untranslated'], ['de']));

        Yii::$app->getI18n()->callback('de', fn() => $this->assertEquals('translated_de', $model->getI18nAttributeName('translated')));

        Yii::$app->language = 'de';
        $this->assertEquals('translated_de', $model->getI18nAttributeName('translated'));
    }

    public function testTranslatedHintsAndRules(): void
    {
        $model = new class() extends Model {
            use I18nAttributesTrait;

            /** @noinspection PhpUnused */
            public ?string $translated_de = '';
            public ?string $translated = 'translated';

            public function init(): void
            {
                $this->i18nAttributes = ['translated'];
                parent::init();
            }

            public function rules(): array
            {
                return $this->getI18nRules([
                    [
                        'translated',
                        'required',
                    ],
                ]);
            }

            public function attributeHints(): array
            {
                return [
                    'translated' => 'This value is required in all languages.',
                ];
            }
        };

        $this->assertFalse($model->validate());
        $this->assertEquals('Translated (DE) cannot be blank.', $model->getFirstError('translated_de'));
        $this->assertEquals('This value is required in all languages.', $model->getAttributeHint('translated_de'));
    }

    public function testTranslatedActiveQuery()
    {
        $model = new class() extends ActiveRecord {
            use I18nAttributesTrait;

            public ?string $translated = 'translated';

            public function init(): void
            {
                $this->i18nAttributes = ['translated'];
                parent::init();
            }

            public static function tableName(): string
            {
                return '{{%test}}';
            }
        };

        Yii::$app->language = 'de';

        /** @var I18nActiveQuery $query */
        $query = $model::find();

        $this->assertEquals('{{%test}}.[[translated_de]]', $query->getI18nAttributeName('translated'));

        $sql = $query
            ->select(['translated'])
            ->replaceI18nAttributes()
            ->createCommand()
            ->sql;

        $this->assertEquals('SELECT `test`.`translated_de` FROM `test`', $sql);
    }

    public function testTranslatedTableNames(): void
    {
        $name = Yii::$app->getI18n()->getTableName('test', 'de');
        $this->assertEquals('{{%test_de}}', $name);
    }
}