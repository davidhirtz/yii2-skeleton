<?php

namespace davidhirtz\yii2\skeleton\tests\unit\behaviors;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\behaviors\SerializedAttributesBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\Redirect;
use Yii;

class SerializedAttributeBehaviorTest extends Unit
{
    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'data' => 'blob null',
        ];

        Yii::$app->getDb()->createCommand()->createTable(SerializedAttributesActiveRecord::tableName(), $columns)->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable(SerializedAttributesActiveRecord::tableName())->execute();
        Redirect::deleteAll();

        parent::_after();
    }

    public function testBeforeSaveEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->beforeSave(true);

        $this->assertEquals(serialize($data), $model->data);
    }

    public function testAfterSaveEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->save();

        $this->assertEquals($data, $model->data);
    }

    public function testBeforeUpdateEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = ['test' => 'test'];
        $model->save();

        $data = ['test' => 'new-test'];
        $model->data = $data;
        $model->beforeSave(true);

        $this->assertEquals(serialize($data), $model->data);
    }

    public function testAfterUpdateEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = ['test' => 'test'];
        $model->save();

        $data = ['test' => 'new-test'];
        $model->data = $data;
        $model->update();

        $this->assertEquals($data, $model->data);
    }


    public function testAfterFindEvent(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->save();

        $model = SerializedAttributesActiveRecord::findOne($model->id);

        $this->assertEquals($data, $model->data);
    }

    public function testEncodedOption(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->getSerializedAttributesBehavior()->encode = true;
        $data = ['test' => 'test'];
        $model->data = $data;
        $model->beforeSave(true);

        $this->assertEquals(base64_encode(serialize($data)), $model->data);

        $model->afterSave(true, ['data' => $model->data]);

        $this->assertEquals($data, $model->data);
    }

    public function testEmptyValue(): void
    {
        $model = new SerializedAttributesActiveRecord();
        $model->data = '';
        $model->beforeSave(true);

        $this->assertNull($model->data);

        $model->afterSave(true, ['data' => $model->data]);

        $this->assertEquals([], $model->data);
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

    public static function tableName(): string
    {
        return 'test_serialized';
    }
}
