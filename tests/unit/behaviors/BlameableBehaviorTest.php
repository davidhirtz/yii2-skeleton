<?php

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use Yii;
use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

class BlameableBehaviorTest extends Unit
{
    public function _before(): void
    {
        Yii::$app->set('user', UserMock::class);
        $this->getUser()->login(10);

        $columns = [
            'name' => 'string',
            'updated_by_user_id' => 'integer null',
            'created_by_user_id' => 'integer null',
        ];

        Yii::$app->getDb()->createCommand()->createTable('test_blame', $columns)->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable('test_blame')->execute();
        parent::_after();
    }

    public function testInsertUserIsGuest(): void
    {
        $this->getUser()->logout();

        $model = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertNull($model->created_by_user_id);
        $this->assertNull($model->updated_by_user_id);
    }

    public function testInsertUserIsNotGuest(): void
    {
        $model = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->beforeSave(true);

        $this->assertNull($model->created_by_user_id);
        $this->assertEquals(10, $model->updated_by_user_id);
    }

    public function testUpdateUserIsNotGuest(): void
    {
        $model = new ActiveRecordBlameable();
        $model->name = __METHOD__;
        $model->save();

        $this->getUser()->login(20);
        $model = ActiveRecordBlameable::findOne(['name' => __METHOD__]);
        $model->name = self::class;
        $model->save();

        $this->assertNull($model->created_by_user_id);
        $this->assertEquals(20, $model->updated_by_user_id);
    }

    public function testInsertCustomValue(): void
    {
        $model = new ActiveRecordBlameable();
        $model->name = __METHOD__;

        $model->getBlameableBehavior()->attributes = [
            $model::EVENT_BEFORE_INSERT => [
                'updated_by_user_id',
                'created_by_user_id',
            ],
        ];

        $model->getBlameableBehavior()->value = 42;
        $model->beforeSave(true);

        $this->assertEquals(42, $model->created_by_user_id);
        $this->assertEquals(42, $model->updated_by_user_id);
    }

    public function testInsertClosure(): void
    {
        $model = new ActiveRecordBlameable();
        $model->name = __METHOD__;

        $model->getBlameableBehavior()->value = fn ($event): int => strlen((string) $event->sender->name);

        $model->beforeSave(true);

        $this->assertEquals(strlen($model->name), $model->updated_by_user_id);
    }

    public function testCustomAttributesAndEvents(): void
    {
        $model = new ActiveRecordBlameable([
            'as blameable' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_by_user_id', 'updated_by_user_id'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by_user_id'],
                ],
            ],
        ]);

        $model->name = __METHOD__;

        $this->assertNull($model->created_by_user_id);
        $this->assertNull($model->updated_by_user_id);

        $this->getUser()->login(20);

        $model->beforeSave(true);
        $this->assertEquals(20, $model->created_by_user_id);
        $this->assertEquals(20, $model->updated_by_user_id);

        $model->save();

        $this->assertEquals(20, $model->created_by_user_id);
        $this->assertEquals(20, $model->updated_by_user_id);

        $this->getUser()->login(30);

        $model->update();

        $this->assertEquals(20, $model->created_by_user_id);
        $this->assertEquals(20, $model->updated_by_user_id);

        $model->name = 'new name';
        $model->update();

        $this->assertEquals(20, $model->created_by_user_id);
        $this->assertEquals(30, $model->updated_by_user_id);
    }

    public function testDefaultValue(): void
    {
        $this->getUser()->logout();

        $model = new ActiveRecordBlameable([
            'as blameable' => [
                'class' => BlameableBehavior::class,
                'defaultValue' => 2
            ],
        ]);

        $model->name = __METHOD__;
        $model->insert();

        $this->assertNull($model->created_by_user_id);
        $this->assertEquals(2, $model->updated_by_user_id);
    }

    private function getUser(): UserMock
    {
        return Yii::$app->get('user');
    }
}

/**
 * @property string $name
 * @property int|null $updated_by_user_id
 * @property int|null $created_by_user_id
 */
class ActiveRecordBlameable extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'BlameableBehavior' => BlameableBehavior::class,
        ];
    }

    public static function tableName(): string
    {
        return 'test_blame';
    }

    public function getBlameableBehavior(): BlameableBehavior
    {
        /** @var BlameableBehavior $behavior */
        $behavior = $this->getBehavior('BlameableBehavior');
        return $behavior;
    }

    public static function primaryKey(): array
    {
        return ['name'];
    }
}

class UserMock extends BaseObject
{
    public ?int $id = null;

    public function login(int $id): void
    {
        $this->id = $id;
    }

    public function logout(): void
    {
        $this->id = null;
    }

    public function getIsGuest(): bool
    {
        return $this->id === null;
    }
}
