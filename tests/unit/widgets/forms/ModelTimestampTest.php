<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\modules\admin\widgets\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\behaviors\TimestampBehavior;
use Hirtz\Skeleton\codeception\traits\AssetDirectoryTrait;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\widgets\bootstrap\ActiveForm;
use Hirtz\Skeleton\widgets\forms\traits\ModelTimestampTrait;
use Hirtz\Timeago\Timeago;
use Yii;

//class ModelTimestampTest extends Unit
//{
//    use AssetDirectoryTrait;
//
//    protected function _before(): void
//    {
//        $columns = [
//            'id' => 'pk',
//            'updated_at' => 'datetime NULL DEFAULT NULL',
//            'created_at' => 'datetime',
//        ];
//
//        Yii::$app->getDb()
//            ->createCommand()
//            ->createTable(TestTimestampActiveRecord::tableName(), $columns)
//            ->execute();
//
//        $this->createAssetDirectory();
//        parent::_before();
//    }
//
//    protected function _after(): void
//    {
//        Yii::$app->getDb()
//            ->createCommand()
//            ->dropTable(TestTimestampActiveRecord::tableName())
//            ->execute();
//
//        $this->removeAssetDirectory();
//        parent::_after();
//    }
//
//    public function testModelTimestampTrait(): void
//    {
//        $model = TestTimestampActiveRecord::create();
//        self::assertTrue($model->insert());
//
//        $form = TestActiveForm::widget([
//            'model' => $model,
//        ]);
//
//        self::assertStringContainsString(Timeago::tag($model->created_at), $form);
//    }
//}
//
///**
// * @property int $id
// * @property DateTime|null $updated_at
// * @property DateTime|null $created_at
// */
//class TestTimestampActiveRecord extends ActiveRecord
//{
//    #[\Override]
//    public function behaviors(): array
//    {
//        return [
//            'DateTimeBehavior' => DateTimeBehavior::class,
//            'TimestampBehavior' => TimestampBehavior::class,
//            ...parent::behaviors(),
//        ];
//    }
//
//    #[\Override]
//    public static function tableName(): string
//    {
//        return 'test_timestamp_table';
//    }
//}
//
///**
// * @property TestTimestampActiveRecord $model
// */
//class TestActiveForm extends ActiveForm
//{
//    use ModelTimestampTrait;
//
//    public function __construct()
//    {
//        $this->action = '/';
//        parent::__construct();
//    }
//}
