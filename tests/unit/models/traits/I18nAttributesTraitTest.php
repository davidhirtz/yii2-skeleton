<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\traits\I18nAttributesTrait;
use Yii;
use yii\base\Model;

class I18nAttributesTraitTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $columns = [
            'id' => 'pk',
            'name' => 'string not null',
            'name_de' => 'string not null',
            'slug' => 'string not null',
            'slug_de' => 'string not null',
            'parent_slug' => 'string null',
            'parent_slug_de' => 'string null',
            'untranslated' => 'string null',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(I18nActiveRecord::tableName(), $columns)
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->createIndex('slug', I18nActiveRecord::tableName(), ['slug', 'parent_slug'], true)
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->createIndex('slug_de', I18nActiveRecord::tableName(), ['slug_de', 'parent_slug_de'], true)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(I18nActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testI18nAttributes(): void
    {
        $model = new I18nActiveRecord();
        $model->name = 'Test Name';
        $model->name_de = 'Test Name DE';

        $this->assertEquals($model->name, $model->getI18nAttribute('name'));
        $this->assertEquals($model->name_de, $model->getI18nAttribute('name', 'de'));

        $this->assertEquals(['en-US' => 'name', 'de' => 'name_de'], $model->getI18nAttributeNames('name'));

        $this->assertEquals('untranslated', $model->getI18nAttributeName('untranslated'));
        $this->assertEquals(['en-US' => 'untranslated'], $model->getI18nAttributeNames('untranslated'));
    }

    public function testI18nRules(): void
    {
        $model = new I18nActiveRecord();
        $rules = $model->rules();

        $this->assertEquals(['name', 'name_de', 'slug', 'slug_de'], $rules[0][0]);
        $this->assertEquals(['name', 'name_de', 'slug', 'slug_de', 'untranslated'], $rules[1][0]);
        $this->assertEquals(['slug_de'], $rules[3]['targetAttribute']);

        $model->name = 'Test Name';
        $model->slug = 'test-name';

        $this->assertFalse($model->validate());

        $model->name_de = 'Test Name DE';
        $model->slug_de = 'test-name-de';

        $this->assertTrue($model->save());

        $newModel = new I18nActiveRecord($model->getAttributes(except: ['id']));

        $this->assertFalse($newModel->save());
        $this->assertEquals(['slug', 'slug_de'], array_keys($newModel->getErrors()));
    }

    public function testI18nRulesWithUniqueTargetAttribute(): void
    {
        $model = new I18nParentSlugActiveRecord();
        $rules = $model->rules();

        $this->assertEquals(['slug', 'parent_slug'], $rules[2]['targetAttribute']);
        $this->assertEquals(['slug_de', 'parent_slug_de'], $rules[3]['targetAttribute']);
    }

    public function testI18nAttributeHints()
    {
        $model = new I18nActiveRecord();

        $this->assertEquals('Part of the URL', $model->getAttributeHint('slug'));
        $this->assertEquals('Part of the URL', $model->getAttributeHint('slug_de'));
    }

    public function testEmptyI18nAttributes()
    {
        $model = new class () extends Model {
            use I18nAttributesTrait;

            public string $name = '';
        };

        $this->assertEquals('Name', $model->getAttributeLabel('name'));
    }
}

/**
 * @property int $id
 * @property string $name
 * @property string $name_de
 * @property string $slug
 * @property string $slug_de
 * @property string $untranslated
 */
class I18nActiveRecord extends ActiveRecord
{
    use I18nAttributesTrait;

    public array|string|null $slugTargetAttribute = null;

    public function init(): void
    {
        $this->i18nAttributes = ['name', 'slug', 'parent_slug'];
        parent::init();
    }

    public function rules(): array
    {
        return $this->getI18nRules([
            [
                ['name', 'slug'],
                'required',
            ],
            [
                ['name', 'slug', 'untranslated'],
                'string',
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => $this->slugTargetAttribute,
            ],
        ]);
    }

    public function attributeHints(): array
    {
        return [
            'slug' => 'Part of the URL',
        ];
    }

    public static function tableName(): string
    {
        return 'i18n_test';
    }
}

/**
 * @property string|null $parent_slug
 * @property string|null $parent_slug_de
 */
class I18nParentSlugActiveRecord extends I18nActiveRecord
{
    public function init(): void
    {
        $this->slugTargetAttribute = ['slug', 'parent_slug'];
        parent::init();
    }
}
