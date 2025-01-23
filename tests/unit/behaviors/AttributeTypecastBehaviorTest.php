<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\behaviors\AttributeTypecastBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\events\CreateValidatorsEvent;
use Yii;
use yii\base\Behavior;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\validators\NumberValidator;

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
        $model->getAttributeTypecastBehavior()->typecastBooleanAsInteger = false;

        $model->setAttribute('name', 123);
        $model->setAttribute('amount', '58');
        $model->setAttribute('price', '100.8');
        $model->setAttribute('is_active', 1);
        $model->setAttribute('callback', 'foo');
        $model->setAttribute('nullable', '');

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertSame('123', $model->name);
        self::assertSame(58, $model->amount);
        self::assertSame(100.8, $model->price);
        self::assertTrue($model->is_active);
        self::assertSame('callback: foo', $model->callback);
        self::assertNull($model->nullable);
    }

    public function testTypecastModelProperties(): void
    {
        $model = new class() extends Model {
            public int|string|null $int = null;
            public float|null $float = null;
            public bool|null $bool = null;
            public string|null $string = null;

            public function behaviors(): array
            {
                return [
                    'AttributeTypecastBehavior' => [
                        'class' => AttributeTypecastBehavior::class,
                        'nullableAttributes' => ['int', 'float', 'bool', 'string'],
                        'typecastBooleanAsInteger' => false,
                    ],
                ];
            }

            public function rules(): array
            {
                return [
                    [
                        ['int'],
                        'number',
                        'integerOnly' => true,
                    ],
                    [
                        ['float'],
                        'number',
                    ],
                    [
                        ['bool'],
                        'boolean',
                    ],
                    [
                        ['string'],
                        'string',
                    ],
                ];
            }
        };

        /** @var AttributeTypecastBehavior $behavior */
        $behavior = $model->getBehavior('AttributeTypecastBehavior');

        $model->int = 0;
        $model->float = 0.0;
        $model->bool = false;
        $model->string = '';

        self::assertNotNull($model->int);
        self::assertNotNull($model->float);
        self::assertNotNull($model->bool);
        self::assertNotNull($model->string);

        $behavior->typecastAttributes();

        self::assertNotNull($model->int);
        self::assertNotNull($model->float);
        self::assertNotNull($model->bool);
        self::assertNull($model->string);

        $model->int = '0';
        $behavior->typecastAttributes();

        self::assertEquals(0, $model->int);

        $model->int = '';
        $behavior->typecastAttributes();

        self::assertNull($model->int);
    }

    public function testSkipNull(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $model->getAttributeTypecastBehavior()->skipOnNull = true;

        $model->setAttribute('name', null);
        $model->setAttribute('amount', null);
        $model->setAttribute('price', null);
        $model->setAttribute('is_active', null);
        $model->setAttribute('callback', null);
        $model->setAttribute('nullable', null);

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertNull($model->name);
        self::assertNull($model->amount);
        self::assertNull($model->price);
        self::assertNull($model->is_active);
        self::assertNull($model->callback);
        self::assertNull($model->nullable);

        $model->getAttributeTypecastBehavior()->skipOnNull = false;
        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertSame('', $model->name);
        self::assertSame(0, $model->amount);
        self::assertSame(0.0, $model->price);
        self::assertSame(0, $model->is_active);
        self::assertSame('callback: ', $model->callback);
        self::assertNull($model->nullable);
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

        $model->setAttribute('amount', '1');
        self::assertTrue($model->validate());
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
        self::assertSame('callback: find', $model->callback);
    }

    public function testEmptyDirtyAttributesAfterFind(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->setAttribute('name', 123);
        $model->setAttribute('amount', '58');
        $model->setAttribute('price', '100.8');
        $model->setAttribute('is_active', 1);
        $model->setAttribute('callback', 'foo');
        $model->setAttribute('nullable', '');

        $model->save(false);

        $model = AttributeTypecastActiveRecord::find()->one();

        self::assertEmpty($model->getDirtyAttributes());
    }

    public function testAfterValidateEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->callback = 'validate';
        $model->validate();
        self::assertSame('callback: validate', $model->callback);
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
        self::assertSame('callback: insert', $model->callback);
        self::assertTrue($beforeInsertHappened);
        $beforeInsertHappened = false;

        $beforeUpdateHappened = false;
        $model->callback = 'update';

        $model->on(AttributeTypecastActiveRecord::EVENT_BEFORE_UPDATE, function () use (&$beforeUpdateHappened): void {
            $beforeUpdateHappened = true;
        });

        $model->save(false);

        self::assertSame('callback: update', $model->callback);
        self::assertTrue($beforeUpdateHappened);
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

        self::assertTrue($beforeInsertHappened);
        self::assertTrue($afterInsertHappened);
        self::assertSame('callback: callback: insert', $model->callback);

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

        self::assertSame('callback: callback: update', $model->callback);
        self::assertTrue($beforeUpdateHappened);
        self::assertTrue($afterUpdateHappened);
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

        self::assertEquals($expectedAttributeTypes, $behavior->attributeTypes);
    }

    public function testAutoDetectAttributeTypesFromBehaviors()
    {
        $model = new class() extends AttributeTypecastActiveRecord {
            use ModelTrait;

            public function behaviors(): array
            {
                $behaviors = parent::behaviors();
                $behaviors['AttributeTypecastBehavior']['attributeTypes'] = null;

                return $behaviors;
            }

            public function rules(): array
            {
                return [];
            }
        };

        $behavior = new class() extends Behavior {
            public function events(): array
            {
                return [
                    CreateValidatorsEvent::EVENT_CREATE_VALIDATORS => function (CreateValidatorsEvent $event) {
                        $event->validators->append(new NumberValidator([
                            'attributes' => ['name'],
                            'integerOnly' => true,
                        ]));
                    },
                ];
            }
        };

        $behavior->attach($model);
        $model->name = '01';

        $model->validate();

        self::assertIsInt($model->name);
    }

    public function testSkipNotSelectedAttribute(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->name = 'skip-not-selected';
        $model->callback = 'foo';
        $model->nullable = '';

        $model->setAttribute('amount', '58');
        $model->setAttribute('price', '100.8');
        $model->setAttribute('is_active', 1);

        $model->save(false);

        $model = AttributeTypecastActiveRecord::find()
            ->select(['id', 'name'])
            ->limit(1)
            ->one();

        $model->getAttributeTypecastBehavior()->typecastAttributes();
        $model->save(false);

        $model->refresh();
        self::assertSame(58, $model->amount);
    }
}

/**
 * @property int $id
 * @property string|null $name
 * @property int|null $amount
 * @property float|null $price
 * @property bool|null $is_active
 * @property string|null $callback
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
