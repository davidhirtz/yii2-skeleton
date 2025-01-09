<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\widgets\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\StatusFieldTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\base\Model;

class StatusFieldTest extends Unit
{
    use AssetDirectoryTrait;

    public function _before(): void
    {
        $this->createAssetDirectory();
        parent::_before();
    }

    public function _after(): void
    {
        $this->removeAssetDirectory();
        parent::_after();
    }

    public function testStatusField(): void
    {
        $html = TestStatusFieldActiveForm::widget();
        self::assertEquals(3, preg_match_all('/<option/', $html));
    }
}

class TestStatusFieldModel extends Model
{
    public ?int $status = null;

    public function rules(): array
    {
        return [
            [
                ['status'],
                'in',
                'range' => self::getStatuses(),
            ],
        ];
    }

    public static function getStatuses(): array
    {
        return [
            1 => [
                'name' => 'Disabled',
            ],
            2 => [
                'name' => 'Draft',
            ],
            3 => [
                'name' => 'Published',
            ],
        ];
    }
}

/**
 * @property TestStatusFieldModel $model
 */
class TestStatusFieldActiveForm extends ActiveForm
{
    use StatusFieldTrait;

    public function init(): void
    {
        $this->action = '/';
        $this->model = new TestStatusFieldModel();
        parent::init();
    }

    public function renderFields(): void
    {
        echo $this->statusField();
    }
}
