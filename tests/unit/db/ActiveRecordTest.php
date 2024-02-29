<?php

namespace davidhirtz\yii2\skeleton\tests\unit\db;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Yii;

class ActiveRecordTest extends Unit
{
    use UserFixtureTrait;

    protected UnitTester $tester;

    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'name' => 'string not null',
            'nullable' => 'string null',
            'user_id' => 'integer null',
            'updated_by_user_id' => 'integer null',
            'updated_at' => 'datetime null',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(TestActiveRecord::tableName(), $columns)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TestActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testFind(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->insert();

        self::assertInstanceOf(ActiveQuery::class, $model::find());
    }

    public function testFindOne(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->insert();

        self::assertNull($model::findOne(null));
        self::assertInstanceOf(TestActiveRecord::class, $model::findOne(1));
    }

    public function testRelationFromForeignKey(): void
    {
        $model = new TestActiveRecord();
        self::assertInstanceOf(UserQuery::class, $model->getRelationFromForeignKey('user_id'));
    }

    public function testRefreshRelation(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->updated_by_user_id = 1;
        $model->insert();

        self::assertEquals(1, $model->updated->id);
        $model->populateRelation('updated', null);

        /** @var User $updated */
        $updated = $model->refreshRelation('updated');
        self::assertEquals(1, $updated->id);
    }

    public function testUpdateAttributesBlameable(): void
    {
        $this->tester->amLoggedInAs(1);

        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->insert();

        self::assertNull($model->updated_by_user_id);
        self::assertNull($model->updated_at);

        $model->updateAttributesBlameable([
            'name' => 'New Test',
            'updated_by_user_id',
            'updated_at',
        ]);

        self::assertEquals('New Test', $model->name);
        self::assertEquals(1, $model->updated_by_user_id);
        self::assertNotNull($model->updated_at);
    }

    public function testBatchInsert(): void
    {
        $data = [
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
            [
                'name' => 'Test 3',
            ],
        ];

        $expected = count($data);

        $count = TestActiveRecord::batchInsert($data);
        self::assertEquals($expected, $count);

        $count = TestActiveRecord::find()->count();
        self::assertEquals($expected, $count);
    }

    /**
     * Tests overriding the identical checks for date attributes.
     */
    public function testGetDirtyAttributes(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->updated_at = new DateTime();
        $model->insert();

        $model = TestActiveRecord::find()->one();
        $model->updated_at = (new DateTime())->setTimestamp($model->updated_at->getTimestamp());

        self::assertEquals(0, count($model->getDirtyAttributes()));
        self::assertFalse($model->isAttributeChanged('updated_at'));
        self::assertFalse($model->hasChangedAttributes(['name', 'updated_at']));

        $model->name = 'New Test';
        self::assertTrue($model->hasChangedAttributes(['name', 'updated_at']));
    }

    public function testIsBatch(): void
    {
        $model = new ActiveRecord();
        $model->setIsBatch(true);
        self::assertTrue($model->getIsBatch());
    }

    public function testIsDeleted(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->insert();

        self::assertFalse($model->isDeleted());

        $model->delete();
        self::assertTrue($model->isDeleted());
    }

    public function testTypecastAttributesBeforeValidate(): void
    {
        $model = new TestActiveRecord();
        $model->name = 'Test';
        $model->nullable = '';

        $model->setAttribute('user_id', '1');
        $model->validate();

        self::assertEquals(1, $model->user_id);
        self::assertEquals(null, $model->nullable);
    }

    public function testAttributeLabels(): void
    {
        self::assertIsArray(ActiveRecord::instance()->attributeLabels());
    }
}

/**
 * @property int $id
 * @property string $name
 * @property string|null $nullable
 * @property int|null $user_id
 * @property int|null $updated_by_user_id
 * @property DateTime|null $updated_at
 */
class TestActiveRecord extends ActiveRecord
{
    use UpdatedByUserTrait;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['name'],
                'required',
            ],
            [
                ['name', 'nullable'],
                'string',
                'max' => 255,
            ],
        ];
    }

    public function getUser(): UserQuery
    {
        /** @var UserQuery $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    public static function tableName(): string
    {
        return 'test_active_record';
    }
}
