<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Model;

class AdminModelTraitTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(AdminModelRecord::tableName(), [
                'id' => 'pk',
                'status' => 'tinyint not null default 1',
                'type' => 'tinyint null',
                'name' => 'string null',
                'name_de' => 'string null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(AdminModelRecord::tableName())
            ->execute();
    }

    public function testAdminNameReturnsTypeWithoutPrimaryKey(): void
    {
        $model = new AdminModelRecord();
        $model->name = 'Test';

        self::assertEquals($model->getAdminType(), $model->getAdminName());
    }

    public function testAdminNameReturnsNameAttribute(): void
    {
        $model = $this->createRecord(['name' => '  Test  ']);

        self::assertEquals('Test', $model->getAdminName());
    }

    public function testAdminNameFallsBackToModelId(): void
    {
        $model = $this->createRecord();

        self::assertEquals(Yii::t('skeleton', 'COMMON_MODEL_ID', [
            'model' => $model->getAdminType(),
            'id' => $model->id,
        ]), $model->getAdminName());
    }

    public function testAdminNameUsesTranslatedAttribute(): void
    {
        $model = $this->createRecord(['name' => 'Example', 'name_de' => 'Beispiel']);

        Yii::$app->language = 'de';
        self::assertEquals('Beispiel', $model->getAdminName());
    }

    public function testAdminNameFallsBackToSourceLanguage(): void
    {
        $model = $this->createRecord(['name' => 'Example']);

        Yii::$app->language = 'de';
        self::assertEquals('Example', $model->getAdminName());
    }

    public function testAdminNameWithoutNameAttribute(): void
    {
        $model = new class () extends Model implements AdminModelInterface {
            use AdminModelTrait;

            public function getAdminRoute(): array|false
            {
                return false;
            }
        };

        self::assertEquals($model->getAdminType(), $model->getAdminName());
    }

    public function testAdminTypeFallsBackToClassName(): void
    {
        self::assertEquals('AdminModelRecord', (new AdminModelRecord())->getAdminType());
    }

    public function testAdminTypeReturnsTypeName(): void
    {
        $model = new AdminModelRecord();
        $model->type = AdminModelRecord::TYPE_DEFAULT;

        self::assertEquals('Test type', $model->getAdminType());
    }

    public function testAdminIconIsNullWithoutTypeOrStatus(): void
    {
        $model = new class () extends Model implements AdminModelInterface {
            use AdminModelTrait;

            public function getAdminRoute(): array|false
            {
                return false;
            }
        };

        self::assertNull($model->getAdminIcon());
    }

    public function testAdminIconPrefersTypeIcon(): void
    {
        $model = new AdminModelRecord();
        $model->type = AdminModelRecord::TYPE_DEFAULT;

        self::assertEquals('star', $model->getAdminIcon());
    }

    public function testAdminIconFallsBackToStatusIcon(): void
    {
        $model = new AdminModelRecord();
        $model->status = AdminModelRecord::STATUS_ENABLED;

        self::assertEquals($model->getStatusIcon(), $model->getAdminIcon());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRecord(array $attributes = []): AdminModelRecord
    {
        $model = new AdminModelRecord();
        $model->setAttributes($attributes, false);
        $model->insert();

        return $model;
    }
}

/**
 * @property int $id
 * @property int $status
 * @property int|null $type
 * @property string|null $name
 * @property string|null $name_de
 */
class AdminModelRecord extends ActiveRecord implements
    AdminModelInterface,
    I18nAttributeInterface,
    StatusAttributeInterface,
    TypeAttributeInterface
{
    use AdminModelTrait;
    use I18nAttributesTrait;
    use StatusAttributeTrait;
    use TypeAttributeTrait;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEFAULT => [
                'name' => 'Test type',
                'icon' => 'star',
            ],
        ];
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/test', 'id' => $this->id] : false;
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%test_admin_model}}';
    }
}
