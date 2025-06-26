<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\traits\TrailModelTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\Exception;

class TrailBehaviorTest extends Unit
{
    protected UnitTester $tester;

    use UserFixtureTrait;

    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'name' => 'string null',
            'excluded' => 'string null',
            'boolean' => 'bool',
            'range' => 'int unsigned',
            'user_id' => 'int unsigned null',
            'datetime' => 'datetime',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(TrailActiveRecord::tableName(), $columns)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TrailActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testAfterInsertEvent(): void
    {
        $model = $this->createTrailActiveRecord();
        self::assertTrue($model->insert());

        $trail = Trail::findOne([
            'model' => $model::class,
            'model_id' => $model->id,
        ]);

        self::assertEquals(Trail::TYPE_CREATE, $trail->type);
    }

    public function testAfterInsertEventWithoutTrailAttributes(): void
    {
        $model = new class() extends TrailActiveRecord {
            public function getTrailAttributes(): array
            {
                return [];
            }
        };

        $model->excluded = 'test';
        $model->insert();

        $trail = $this->findLatestTrailForActiveRecord($model);

        self::assertEquals(Trail::TYPE_CREATE, $trail->type);
        self::assertArrayHasKey('excluded', $trail->data);
    }

    public function testAfterUpdateEvent(): void
    {
        $model = $this->createTrailActiveRecord();
        $model->insert();

        $model->name = 'test name updated';
        $model->excluded = 'test excluded updated';

        self::assertEquals(1, $model->update());

        $trail = $this->findLatestTrailForActiveRecord($model);

        self::assertEquals(Trail::TYPE_UPDATE, $trail->type);
        self::assertEquals(['test name', 'test name updated'], $trail->data['name']);
        self::assertNotContains('excluded', $trail->data);
    }

    public function testAfterDeleteEvent(): void
    {
        $model = $this->createTrailActiveRecord();
        $model->insert();

        self::assertEquals(1, $model->delete());

        $trail = $this->findLatestTrailForActiveRecord($model);
        self::assertEquals(Trail::TYPE_DELETE, $trail->type);
    }

    public function testFailedInsertTrail()
    {
        $model = new class() extends TrailActiveRecord {
            public function behaviors(): array
            {
                return [
                    'TrailBehavior' => TrailBehaviorMock::class,
                ];
            }
        };

        $model->name = 'test';
        self::assertTrue($model->insert());
    }

    public function testTrailModelName(): void
    {
        $name = (new ReflectionClass(TrailActiveRecord::class))->getShortName();
        self::assertEquals($name, TrailActiveRecord::instance()->getTrailModelName());
    }

    public function testTrailModelType(): void
    {
        self::assertNull(TrailActiveRecord::instance()->getTrailModelType());
    }

    public function testTrailModelAdminRoute(): void
    {
        $model = TrailActiveRecord::create();
        self::assertFalse($model->getTrailModelAdminRoute());

        $model = new class() extends TrailActiveRecord {
            public function getAdminRoute(): array
            {
                return ['/admin/test'];
            }
        };

        self::assertEquals($model->getAdminRoute(), $model->getTrailModelAdminRoute());
    }

    public function testTrailParents(): void
    {
        self::assertNull(TrailActiveRecord::instance()->getTrailParents());
    }

    public function testFormatTrailAttributeValue(): void
    {
        $model = $this->createTrailActiveRecord();
        $model->user_id = 1;
        $model->insert();

        $trail = $this->findLatestTrailForActiveRecord($model);

        self::assertEquals('Yes', $model->formatTrailAttributeValue('boolean', $trail->data['boolean']));
        self::assertEquals('three', $model->formatTrailAttributeValue('range', $trail->data['range']));

        $expected = Yii::$app->getFormatter()->asDatetime($model->datetime, 'medium');
        self::assertEquals($expected, $model->formatTrailAttributeValue('datetime', $trail->data['datetime']));

        $user = $model->formatTrailAttributeValue('user_id', $trail->data['user_id']);
        self::assertIsObject($user);
        self::assertEquals(1, $user->id);

        $data = ['one', 'two'];
        self::assertEquals(print_r($data, true), $model->formatTrailAttributeValue('name', $data));
    }

    public function testFormatTrailAttributeValueWithoutRange(): void
    {
        $model = new class() extends Model implements TrailModelInterface {
            use TrailModelTrait;

            public ?int $value = null;

            public function behaviors(): array
            {
                return [
                    'TrailBehavior' => TrailBehavior::class,
                ];
            }

            public function rules(): array
            {
                return [
                    [
                        ['value'],
                        'in',
                        'range' => [1, 2, 3],
                    ]
                ];
            }
        };

        self::assertEquals(2, $model->formatTrailAttributeValue('value', 2));
    }

    private function createTrailActiveRecord(): TrailActiveRecord
    {
        $model = new TrailActiveRecord();
        $model->name = 'test name';
        $model->excluded = 'test excluded';
        $model->boolean = true;
        $model->range = 3;
        $model->datetime = new DateTime();

        return $model;
    }

    private function findLatestTrailForActiveRecord(TrailActiveRecord $model)
    {
        return Trail::find()
            ->where([
                'model' => $model::class,
                'model_id' => $model->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }
}

/**
 * @property int $id
 * @property string|null $name
 * @property string $excluded
 * @property bool|int $boolean
 * @property int $range
 * @property int $user_id
 * @property DateTime $datetime
 *
 * @property-read User $user
 *
 * @mixin TrailBehavior
 */
class TrailActiveRecord extends ActiveRecord implements TrailModelInterface
{
    use TrailModelTrait;

    #[\Override]
    public function behaviors(): array
    {
        return [
            'TrailBehavior' => [
                'class' => TrailBehavior::class,
                'exclude' => [
                    'id',
                    'excluded',
                ],
            ]
        ];
    }

    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['name', 'excluded'],
                'string',
            ],
            [
                ['boolean'],
                'boolean',
            ],
            [
                ['range'],
                'in',
                'range' => array_keys($this->getRanges()),
            ],
            [
                ['datetime'],
                DateTimeValidator::class,
            ],
        ];
    }

    /**
     * @return UserQuery<User>
     */
    public function getUser(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    public function getRanges(): array
    {
        return [
            1 => 'one',
            2 => 'two',
            3 => 'three',
        ];
    }

    #[\Override]
    public static function tableName(): string
    {
        return 'trail_test';
    }
}

class TrailBehaviorMock extends TrailBehavior
{
    #[\Override]
    protected function insertTrail(Trail $trail): void
    {
        $trail = new class() extends Trail {
            public function insert($runValidation = true, $attributes = null)
            {
                throw new Exception("Mocked error message");
            }
        };

        parent::insertTrail($trail);
    }
}
