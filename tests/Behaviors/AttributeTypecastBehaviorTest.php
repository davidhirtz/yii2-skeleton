<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Behaviors;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Events\CreateValidatorsEvent;
use Hirtz\Skeleton\Validators\Interfaces\AttributeTypeInterface;
use Override;
use Yii;
use yii\base\Behavior;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\validators\NumberValidator;
use yii\validators\Validator;
use Closure;

class AttributeTypecastBehaviorTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        AttributeTypecastBehavior::clearAutoDetectedAttributeTypes();

        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        $columns = [
            'id' => 'pk',
            'name' => 'string not null',
            'amount' => 'integer not null',
            'price' => 'float not null',
            'is_active' => 'boolean not null',
            'callback' => 'string not null',
            'ratio' => 'decimal(5,2) null',
            'nullable' => 'string null default null',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(AttributeTypecastActiveRecord::tableName(), $columns)
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable(AttributeTypecastActiveRecord::tableName())->execute();
    }

    public function testTypecast(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->setAttribute('name', 123);
        $model->setAttribute('amount', '58');
        $model->setAttribute('price', '100.8');
        $model->setAttribute('is_active', true);
        $model->setAttribute('callback', 'foo');
        $model->setAttribute('ratio', 12.3);
        $model->setAttribute('nullable', '');

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertSame('123', $model->name);
        self::assertSame(58, $model->amount);
        self::assertSame(100.8, $model->price);
        self::assertSame(1, $model->is_active);
        self::assertSame('FOO', $model->callback);
        self::assertSame('12.30', $model->ratio);
        self::assertNull($model->nullable);
    }

    public function testInvalidValuesAreLeftForTheValidator(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->setAttribute('amount', 'abc');
        $model->setAttribute('price', '1,5');
        $model->setAttribute('is_active', 'maybe');
        $model->setAttribute('ratio', 'abc');

        self::assertFalse($model->validate());
        self::assertSame(['amount', 'price', 'is_active', 'ratio'], array_keys($model->getErrors()));

        self::assertSame('abc', $model->amount);
        self::assertSame('1,5', $model->price);
        self::assertSame('maybe', $model->is_active);
        self::assertSame('abc', $model->ratio);
    }

    public function testTypecastOnlyLossless(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $behavior = $model->getAttributeTypecastBehavior();

        $model->setAttribute('amount', ' -5 ');
        $behavior->typecastAttributes(['amount']);
        self::assertSame(-5, $model->amount);

        $model->setAttribute('amount', 2.0);
        $behavior->typecastAttributes(['amount']);
        self::assertSame(2, $model->amount);

        $model->setAttribute('amount', 2.5);
        $behavior->typecastAttributes(['amount']);
        self::assertSame(2.5, $model->amount);

        $model->setAttribute('amount', '1.0');
        $behavior->typecastAttributes(['amount']);
        self::assertSame('1.0', $model->amount);

        $model->setAttribute('amount', '');
        $behavior->typecastAttributes(['amount']);
        self::assertSame(0, $model->amount);

        $model->setAttribute('price', '1e3');
        $behavior->typecastAttributes(['price']);
        self::assertSame(1000.0, $model->price);

        $model->setAttribute('is_active', 'on');
        $behavior->typecastAttributes(['is_active']);
        self::assertSame('on', $model->is_active);

        $model->setAttribute('is_active', '0');
        $behavior->typecastAttributes(['is_active']);
        self::assertSame(0, $model->is_active);

        $model->setAttribute('name', ['array']);
        $behavior->typecastAttributes(['name']);
        self::assertSame(['array'], $model->name);
    }

    public function testTypecastModelProperties(): void
    {
        $model = new class () extends Model {
            public int|string|null $int = null;
            public float|null $float = null;
            public int|bool|null $bool = null;
            public string|null $string = null;

            public function behaviors(): array
            {
                return [
                    'AttributeTypecastBehavior' => [
                        'class' => AttributeTypecastBehavior::class,
                        'nullableAttributes' => ['int', 'float', 'bool', 'string'],
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

        $behavior->typecastAttributes();

        self::assertSame(0, $model->int);
        self::assertSame(0.0, $model->float);
        self::assertSame(0, $model->bool);
        self::assertNull($model->string);

        $model->int = '0';
        $behavior->typecastAttributes();

        self::assertSame(0, $model->int);

        $model->int = '';
        $behavior->typecastAttributes();

        self::assertNull($model->int);
    }

    public function testSkipNull(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->setAttribute('name', null);
        $model->setAttribute('amount', null);
        $model->setAttribute('price', null);
        $model->setAttribute('is_active', null);
        $model->setAttribute('callback', null);
        $model->setAttribute('ratio', null);
        $model->setAttribute('nullable', null);

        $model->getAttributeTypecastBehavior()->typecastAttributes();

        self::assertNull($model->name);
        self::assertNull($model->amount);
        self::assertNull($model->price);
        self::assertNull($model->is_active);
        self::assertNull($model->callback);
        self::assertNull($model->ratio);
        self::assertNull($model->nullable);
    }

    public function testAfterLoadEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();
        $model->load(['amount' => '58', 'ratio' => '1.5', 'nullable' => ''], '');

        self::assertSame(58, $model->amount);
        self::assertSame('1.50', $model->ratio);
        self::assertNull($model->nullable);
    }

    public function testBeforeValidateEvent(): void
    {
        $model = new class () extends AttributeTypecastActiveRecord {
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

        self::assertTrue($model->save());

        $model->setAttribute('amount', '1');
        self::assertTrue($model->validate());
    }

    public function testBeforeSaveEvent(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->name = 'name';
        $model->amount = 1;
        $model->price = 100.1;
        $model->is_active = true;
        $model->callback = 'insert';

        $model->save(false);
        self::assertSame('INSERT', $model->callback);
        self::assertSame(1, $model->is_active);

        $model->callback = 'update';
        $model->setAttribute('amount', '2');

        $model->save(false);

        self::assertSame('UPDATE', $model->callback);
        self::assertSame(2, $model->amount);
    }

    public function testNoDirtyAttributesAfterFind(): void
    {
        $model = new AttributeTypecastActiveRecord();

        $model->setAttribute('name', 123);
        $model->setAttribute('amount', '58');
        $model->setAttribute('price', '100.8');
        $model->setAttribute('is_active', true);
        $model->setAttribute('callback', 'foo');
        $model->setAttribute('ratio', '12.3');
        $model->setAttribute('nullable', '');

        self::assertTrue($model->save());

        $model = AttributeTypecastActiveRecord::findOne($model->id);
        self::assertNotNull($model);
        self::assertSame('12.30', $model->ratio);

        $model->load([
            'name' => '123',
            'amount' => '58',
            'price' => '100.8',
            'is_active' => '1',
            'callback' => 'foo',
            'ratio' => '12.3',
            'nullable' => '',
        ], '');

        self::assertTrue($model->validate());
        self::assertSame([], $model->getDirtyAttributes());
    }

    public function testAutoDetectAttributeTypes(): void
    {
        $attributes = [
            'name' => '',
            'amount' => '',
            'price' => '',
            'is_active' => '',
            'nullable' => '',
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

    public function testAutoDetectAttributeTypesFromBehaviors(): void
    {
        $model = new class () extends AttributeTypecastActiveRecord {
            use ModelTrait;

            public function behaviors(): array
            {
                $behaviors = parent::behaviors();
                $typecast = $behaviors['AttributeTypecastBehavior'];

                $behaviors['AttributeTypecastBehavior'] = [
                    'class' => AttributeTypecastBehavior::class,
                    ...(array)$typecast,
                    'attributeTypes' => null,
                ];

                return $behaviors;
            }

            public function rules(): array
            {
                return [];
            }
        };

        $behavior = new class () extends Behavior {
            /**
             * @return array<string, string|Closure>
             */
            public function events(): array
            {
                return [
                    CreateValidatorsEvent::EVENT_CREATE_VALIDATORS => function (CreateValidatorsEvent $event): void {
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

    public function testAutoDetectAttributeTypesFromValidatorInterface(): void
    {
        $model = (new DynamicModel(['amount' => '']))
            ->addRule('amount', new class () extends Validator implements AttributeTypeInterface {
                #[Override]
                public function getAttributeType(): string
                {
                    return AttributeTypecastBehavior::TYPE_INTEGER;
                }

                #[Override]
                public function validateAttribute($model, $attribute): void
                {
                }
            });

        $behavior = new AttributeTypecastBehavior();
        $behavior->attach($model);

        self::assertSame(['amount' => AttributeTypecastBehavior::TYPE_INTEGER], $behavior->attributeTypes);
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
 * @property float|string|null $ratio
 * @property string|null $nullable
 *
 * @property AttributeTypecastBehavior $attributeTypecastBehavior
 */
class AttributeTypecastActiveRecord extends ActiveRecord
{
    #[Override]
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
                    'callback' => fn ($value) => mb_strtoupper((string)$value),
                    'ratio' => AttributeTypecastBehavior::TYPE_FLOAT,
                    'nullable' => AttributeTypecastBehavior::TYPE_STRING,
                ],
            ],
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'test_attribute_typecast';
    }

    #[Override]
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
                ['ratio'],
                'number',
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
