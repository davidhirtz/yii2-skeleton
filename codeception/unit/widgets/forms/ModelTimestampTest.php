<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\Modules\Admin\Widgets\Forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Codeception\traits\AssetDirectoryTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Widgets\Bootstrap\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Traits\ModelTimestampTrait;
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
