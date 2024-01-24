<?php

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\AttributeTypecastBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Yii;
use yii\base\DynamicModel;

class AttributeTypecastBehaviorTest extends Unit
{
    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'name' => 'string not null',
            'amount' => 'integer not null',
            'price' => 'float not null',
            'is_active' => 'boolean not null',
            'callback' => 'string not null',
            'nullable' => 'string null default null',
        ];

        Yii::$app->getDb()->createCommand()->createTable(AttributeTypecastActiveRecord::tableName(), $columns)->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable(AttributeTypecastActiveRecord::tableName())->execute();
        AttributeTypecastBehavior::clearAutoDetectedAttributeTypes();

        parent::_after();
    }

    public function testTypecast(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $model->getAttributeTypecastBehavior()->castBooleansAsInt = false;

        $model->name = 123; // @phpstan-ignore-line
        $model->amount = '58'; // @phpstan-ignore-line
        $model->price = '100.8'; // @phpstan-ignore-line
        $model->is_active = 1; // @phpstan-ignore-line
        $model->callback = 'foo';
        $model->nullable = '';

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        $this->assertSame('123', $model->name);
        $this->assertSame(58, $model->amount);
        $this->assertSame(100.8, $model->price);
        $this->assertTrue($model->is_active);
        $this->assertSame('callback: foo', $model->callback);
        $this->assertNull($model->nullable);
    }

    public function testSkipNull(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $model->getAttributeTypecastBehavior()->skipOnNull = true;

        $model->name = null; // @phpstan-ignore-line
        $model->amount = null; // @phpstan-ignore-line
        $model->price = null; // @phpstan-ignore-line
        $model->is_active = null; // @phpstan-ignore-line
        $model->callback = null; // @phpstan-ignore-line
        $model->nullable = null;

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        $this->assertNull($model->name);
        $this->assertNull($model->amount);
        $this->assertNull($model->price);
        $this->assertNull($model->is_active);
        $this->assertNull($model->callback);
        $this->assertNull($model->nullable);

        $model->getAttributeTypecastBehavior()->skipOnNull = false;
        $model->getAttributeTypecastBehavior()->typecastAttributes();

        $this->assertSame('', $model->name);
        $this->assertSame(0, $model->amount);
        $this->assertSame(0.0, $model->price);
        $this->assertSame(0, $model->is_active);
        $this->assertSame('callback: ', $model->callback);
        $this->assertNull($model->nullable);
    }

    public function testBeforeValidateEvent(): void
    {
        $model = new class() extends AttributeTypecastActiveRecord {
            public bool $typecastBeforeValidate = true;

            public function rules(): array
            {
                return [
                    ...parent::rules(),
                    [
                        ['amount'],
                        function ($attribute): void {
                            if (!$this->getIsNewRecord() && $this->isAttributeChanged($attribute)) {
                                $this->addInvalidAttributeError($attribute);
                            }
                        },
                    ],
                ];
            }
        };

        $model->name = 'name';
        $model->amount = 1;
        $model->price = 100.1;
        $model->is_active = true;
        $model->callback = '';

        $model->save();

        $model->amount = '1'; // @phpstan-ignore-line
        $this->assertTrue($model->validate());
    }

    public function testAfterFindEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->name = 'name';
        $model->amount = 1;
        $model->price = 100.1;
        $model->is_active = true;
        $model->callback = '';

        $model->validate();
        $model->save(false);

        $model->updateAll(['callback' => 'find']);
        $model->refresh();
        $this->assertSame('callback: find', $model->callback);
    }

    public function testDirtyAttributesAreEmptyAfterFind(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->name = 123; // @phpstan-ignore-line
        $model->amount = '58'; // @phpstan-ignore-line
        $model->price = '100.8'; // @phpstan-ignore-line
        $model->is_active = 1; // @phpstan-ignore-line
        $model->callback = 'foo';
        $model->nullable = '';

        $model->save(false);

        $model = AttributeTypecastActiveRecord::find()->one();

        $this->assertEmpty($model->getDirtyAttributes());
    }

    public function testAfterValidateEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->callback = 'validate';
        $model->validate();
        $this->assertSame('callback: validate', $model->callback);
    }

    public function testBeforeSaveEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $beforeInsertHappened = false;

        $model->name = 'name';
        $model->amount = 1;
        $model->price = 100.1;
        $model->is_active = true;
        $model->callback = 'insert';

        $model->on(AttributeTypecastActiveRecord::EVENT_BEFORE_INSERT, function () use (&$beforeInsertHappened): void {
            $beforeInsertHappened = true;
        });

        $model->save(false);
        $this->assertSame('callback: insert', $model->callback);
        $this->assertTrue($beforeInsertHappened);
        $beforeInsertHappened = false;

        $beforeUpdateHappened = false;
        $model->callback = 'update';

        $model->on(AttributeTypecastActiveRecord::EVENT_BEFORE_UPDATE, function () use (&$beforeUpdateHappened): void {
            $beforeUpdateHappened = true;
        });

        $model->save(false);
        $this->assertSame('callback: update', $model->callback);
        $this->assertTrue($beforeUpdateHappened);
        $this->assertFalse($beforeInsertHappened);
    }

    public function testAfterSaveEvent(): void
    {
        $model = new AttributeTypecastActiveRecord([
            'typecastAfterSave' => true
        ]);

        $model->name = 'name';
        $model->amount = 1;
        $model->price = 100.1;
        $model->is_active = true;
        $model->callback = 'insert';

        $beforeInsertHappened = false;

        $model->on(AttributeTypecastActiveRecord::EVENT_BEFORE_INSERT, function () use (&$beforeInsertHappened): void {
            $beforeInsertHappened = true;
        });

        $afterInsertHappened = false;

        $model->on(AttributeTypecastActiveRecord::EVENT_AFTER_INSERT, function () use (&$afterInsertHappened): void {
            $afterInsertHappened = true;
        });

        $model->save(false);

        $this->assertTrue($beforeInsertHappened);
        $this->assertTrue($afterInsertHappened);
        $this->assertSame('callback: callback: insert', $model->callback);

        $beforeInsertHappened = false;
        $afterInsertHappened = false;

        $model->callback = 'update';
        $beforeUpdateHappened = false;

        $model->on(AttributeTypecastActiveRecord::EVENT_BEFORE_UPDATE, function () use (&$beforeUpdateHappened): void {
            $beforeUpdateHappened = true;
        });

        $afterUpdateHappened = false;

        $model->on(AttributeTypecastActiveRecord::EVENT_AFTER_UPDATE, function () use (&$afterUpdateHappened): void {
            $afterUpdateHappened = true;
        });

        $model->save(false);

        $this->assertSame('callback: callback: update', $model->callback);
        $this->assertTrue($beforeUpdateHappened);
        $this->assertTrue($afterUpdateHappened);
        $this->assertFalse($beforeInsertHappened);
        $this->assertFalse($afterInsertHappened);
    }

    public function testAutoDetectAttributeTypes(): void
    {
        $attributes = [
            'name' => null,
            'amount' => null,
            'price' => null,
            'is_active' => null,
            'nullable' => null,
        ];
        $model = (new DynamicModel($attributes))
            ->addRule('name', 'string')
            ->addRule('amount', 'integer')
            ->addRule('price', 'number')
            ->addRule('!is_active', 'boolean')
            ->addRule('nullable', 'string');

        $behavior = new AttributeTypecastBehavior();

        $behavior->attach($model);

        $expectedAttributeTypes = [
            'name' => AttributeTypecastBehavior::TYPE_STRING,
            'amount' => AttributeTypecastBehavior::TYPE_INTEGER,
            'price' => AttributeTypecastBehavior::TYPE_FLOAT,
            'is_active' => AttributeTypecastBehavior::TYPE_BOOLEAN,
            'nullable' => AttributeTypecastBehavior::TYPE_STRING,
        ];

        $this->assertEquals($expectedAttributeTypes, $behavior->attributeTypes);
    }

    public function testSkipNotSelectedAttribute(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->name = 'skip-not-selected';
        $model->amount = '58'; // @phpstan-ignore-line
        $model->price = '100.8'; // @phpstan-ignore-line
        $model->is_active = 1; // @phpstan-ignore-line
        $model->callback = 'foo';
        $model->nullable = '';

        $model->save(false);

        $model = AttributeTypecastActiveRecord::find()
            ->select(['id', 'name'])
            ->limit(1)
            ->one();

        $model->getAttributeTypecastBehavior()->typecastAttributes();
        $model->save(false);

        $model->refresh();
        $this->assertSame(58, $model->amount);
    }
}

/**
 * @property int $id
 * @property string $name
 * @property int $amount
 * @property float $price
 * @property bool $is_active
 * @property string $callback
 * @property string|null $nullable
 *
 * @property AttributeTypecastBehavior $attributeTypecastBehavior
 */
class AttributeTypecastActiveRecord extends ActiveRecord
{
    public bool $typecastBeforeValidate = false;
    public bool $typecastAfterSave = false;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'AttributeTypecastBehavior' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'name' => AttributeTypecastBehavior::TYPE_STRING,
                    'amount' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'price' => AttributeTypecastBehavior::TYPE_FLOAT,
                    'is_active' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'callback' => fn ($value) => "callback: $value",
                    'nullable' => AttributeTypecastBehavior::TYPE_STRING,
                ],
                'typecastBeforeValidate' => $this->typecastBeforeValidate,
                'typecastAfterValidate' => true,
                'typecastBeforeSave' => true,
                'typecastAfterFind' => true,
                'typecastAfterSave' => $this->typecastAfterSave,
            ],
        ];
    }

    public static function tableName(): string
    {
        return 'test_attribute_typecast';
    }

    public function rules(): array
    {
        return [
            [
                ['name'],
                'string',
            ],
            [
                ['amount'],
                'integer',
            ],
            [
                ['price'],
                'number',
            ],
            [
                ['is_active'],
                'boolean',
            ],
            [
                ['nullable'],
                'string',
            ],
        ];
    }

    public function getAttributeTypecastBehavior(): AttributeTypecastBehavior
    {
        /** @var AttributeTypecastBehavior $behavior */
        $behavior = $this->getBehavior('AttributeTypecastBehavior');
        return $behavior;
    }
}
