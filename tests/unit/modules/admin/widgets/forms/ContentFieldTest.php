<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\widgets\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\assets\TinyMceAssetBundle;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ContentFieldTrait;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use Yii;
use yii\base\Model;

class ContentFieldTest extends Unit
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

    public function testContentField(): void
    {
        $form = TestContentFieldActiveForm::widget();
        self::assertStringContainsString('textarea', $form);
        self::assertArrayHasKey(TinyMceAssetBundle::class, Yii::$app->getAssetManager()->bundles);
    }
}

class TestContentFieldModel extends Model
{
    public string $contentType = 'html';
    public ?string $content = null;

    public function rules(): array
    {
        return [
            [
                ['content'],
                HtmlValidator::class,
            ],
        ];
    }
}

/**
 * @property TestContentFieldModel $model
 */
class TestContentFieldActiveForm extends ActiveForm
{
    use ContentFieldTrait;

    public function init(): void
    {
        $this->action = '/';
        $this->model = new TestContentFieldModel();
        parent::init();
    }

    public function renderFields(): void
    {
        echo $this->contentField();
    }
}
