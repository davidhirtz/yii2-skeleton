<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\models\traits\I18nAttributesTrait;
use Override;
use Yii;

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
            ->createTable(TestI18nActiveRecord::tableName(), $columns)
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->createIndex('slug', TestI18nActiveRecord::tableName(), ['slug', 'parent_slug'], true)
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->createIndex('slug_de', TestI18nActiveRecord::tableName(), ['slug_de', 'parent_slug_de'], true)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TestI18nActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testI18nAttributes(): void
    {
        $model = new TestI18nActiveRecord();
        $model->name = 'Test Name';
        $model->name_de = 'Test Name DE';

        self::assertTrue($model->isI18nAttribute('name'));

        self::assertEquals($model->name, $model->getI18nAttribute('name'));
        self::assertEquals($model->name_de, $model->getI18nAttribute('name', 'de'));

        self::assertEquals(['en-US' => 'name', 'de' => 'name_de'], $model->getI18nAttributeNames('name'));

        self::assertFalse($model->isI18nAttribute('untranslated'));
        self::assertEquals('untranslated', $model->getI18nAttributeName('untranslated'));
        self::assertEquals(['en-US' => 'untranslated'], $model->getI18nAttributeNames('untranslated'));

        Yii::$app->getI18n()->callback('de', fn () => self::assertEquals('name_de', $model->getI18nAttributeName('name')));
    }

    public function testI18nRules(): void
    {
        $model = new TestI18nActiveRecord();
        $rules = $model->rules();

        self::assertEquals(['name', 'name_de', 'slug', 'slug_de'], $rules[0][0]);
        self::assertEquals(['name', 'name_de', 'slug', 'slug_de', 'untranslated'], $rules[1][0]);
        self::assertEquals(['slug_de'], $rules[3]['targetAttribute']);

        $model->name = 'Test Name';
        $model->slug = 'test-name';

        self::assertFalse($model->validate());
        self::assertEquals('Name (DE) cannot be blank.', $model->getFirstError('name_de'));

        $model->name_de = 'Test Name DE';
        $model->slug_de = 'test-name-de';

        self::assertTrue($model->save());

        $newModel = new TestI18nActiveRecord($model->getAttributes(except: ['id']));

        self::assertFalse($newModel->save());
        self::assertEquals(['slug', 'slug_de'], array_keys($newModel->getErrors()));
    }

    public function testI18nRulesWithUniqueTargetAttribute(): void
    {
        $model = new TestI18nParentSlugActiveRecord();
        $rules = $model->rules();

        self::assertEquals(['slug', 'parent_slug'], $rules[2]['targetAttribute']);
        self::assertEquals(['slug_de', 'parent_slug_de'], $rules[3]['targetAttribute']);
    }

    public function testI18nAttributeHints(): void
    {
        $model = new TestI18nActiveRecord();

        self::assertEquals('Part of the URL', $model->getAttributeHint('slug'));
        self::assertEquals('Part of the URL', $model->getAttributeHint('slug_de'));
    }

    public function testEmptyI18nAttributes(): void
    {
        $model = new class () extends ActiveRecord {
            use I18nAttributesTrait;

            public string $name = '';
        };

        self::assertEquals('Name', $model->getAttributeLabel('name'));
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
class TestI18nActiveRecord extends ActiveRecord implements I18nAttributeInterface
{
    use I18nAttributesTrait;

    public array|string|null $slugTargetAttribute = null;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name', 'slug', 'parent_slug'];
        parent::init();
    }

    #[Override]
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

    #[Override]
    public function attributeHints(): array
    {
        return [
            'slug' => 'Part of the URL',
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'i18n_test';
    }
}

/**
 * @property string|null $parent_slug
 * @property string|null $parent_slug_de
 */
class TestI18nParentSlugActiveRecord extends TestI18nActiveRecord
{
    #[Override]
    public function init(): void
    {
        $this->slugTargetAttribute = ['slug', 'parent_slug'];
        parent::init();
    }
}
