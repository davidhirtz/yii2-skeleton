<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\widgets\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

class ModelTimestampTest extends Unit
{
    use AssetDirectoryTrait;

    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'updated_at' => 'datetime NULL DEFAULT NULL',
            'created_at' => 'datetime',
        ];

        Yii::$app->getDb()
            ->createCommand()
            ->createTable(TestTimestampActiveRecord::tableName(), $columns)
            ->execute();

        $this->createAssetDirectory();
        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(TestTimestampActiveRecord::tableName())
            ->execute();

        $this->removeAssetDirectory();
        parent::_after();
    }

    public function testModelTimestampTrait(): void
    {
        $model = TestTimestampActiveRecord::create();
        self::assertTrue($model->insert());

        $form = TestActiveForm::widget([
            'model' => $model,
        ]);

        self::assertStringContainsString(Timeago::tag($model->created_at), $form);
    }
}

/**
 * @property int $id
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_at
 */
class TestTimestampActiveRecord extends ActiveRecord
{
    #[\Override]
    public function behaviors(): array
    {
        return [
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
            ...parent::behaviors(),
        ];
    }

    #[\Override]
    public static function tableName(): string
    {
        return 'test_timestamp_table';
    }
}

/**
 * @property TestTimestampActiveRecord $model
 */
class TestActiveForm extends ActiveForm
{
    use ModelTimestampTrait;

    public function __construct()
    {
        $this->action = '/';
        parent::__construct();
    }
}
