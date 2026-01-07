<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Behaviors;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Behaviors\SerializedAttributesBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Yii;

class SerializedAttributeBehaviorTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $columns = [
            'id' => 'pk',
            'data' => 'blob null',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(SerializedAttributesActiveRecord::tableName(), $columns)
            ->execute();
    }

    #[\Override]
    protected function tearDown(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(SerializedAttributesActiveRecord::tableName())
            ->execute();

        parent::tearDown();
    }

    public function testBeforeSaveEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->beforeSave(true);

        self::assertEquals(serialize($data), $model->data);
    }

    public function testAfterSaveEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->save();

        self::assertEquals($data, $model->data);
    }

    public function testBeforeUpdateEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = ['test' => 'test'];
        $model->save();

        $data = ['test' => 'new-test'];
        $model->data = $data;
        $model->beforeSave(true);

        self::assertEquals(serialize($data), $model->data);
    }

    public function testAfterUpdateEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = ['test' => 'test'];
        $model->save();

        $data = ['test' => 'new-test'];
        $model->data = $data;
        $model->update();

        self::assertEquals($data, $model->data);
    }

    public function testAfterFindEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->save();

        $model = SerializedAttributesActiveRecord::findOne($model->id);

        self::assertEquals($data, $model->data);
    }

    public function testEncodedOption(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->getSerializedAttributesBehavior()->encode = true;
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->beforeSave(true);

        self::assertEquals(base64_encode(serialize($data)), $model->data);

        $model->afterSave(true, ['data' => $model->data]);

        self::assertEquals($data, $model->data);
    }

    public function testEmptyValue(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = '';
        $model->beforeSave(true);

        self::assertNull($model->data);

        $model->afterSave(true, ['data' => $model->data]);

        self::assertEquals([], $model->data);
    }
}

/**
 * @property int $id
 * @property mixed $data
 *
 * @mixin SerializedAttributesBehavior
 */
class SerializedAttributesActiveRecord extends ActiveRecord
{
    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'SerializedAttributesBehavior' => [
                'class' => SerializedAttributesBehavior::class,
                'attributes' => ['data'],
            ],
        ];
    }

    public function getSerializedAttributesBehavior(): SerializedAttributesBehavior
    {
        /** @var SerializedAttributesBehavior $behavior */
        $behavior = $this->getBehavior('SerializedAttributesBehavior');
        return $behavior;
    }

    #[\Override]
    public static function tableName(): string
    {
        return 'test_serialized';
    }
}
