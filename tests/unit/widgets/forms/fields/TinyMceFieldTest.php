<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\widgets\forms\fields;

use Codeception\Test\Unit;
use Hirtz\Skeleton\assets\TinyMceAssetBundle;
use Hirtz\Skeleton\codeception\traits\AssetDirectoryTrait;
use Hirtz\Skeleton\validators\HtmlValidator;
use Hirtz\Skeleton\widgets\forms\ActiveForm;
use Override;
use Yii;
use yii\base\Model;

class TinyMceFieldTest extends Unit
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
        $form = TestContentFieldActiveForm::make()->render();

        self::assertStringContainsString('textarea', $form);
        self::assertArrayHasKey(TinyMceAssetBundle::class, Yii::$app->getAssetManager()->bundles);
    }
}

class TestContentFieldModel extends Model
{
    public string $contentType = 'html';
    public ?string $content = null;

    #[Override]
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

class TestContentFieldActiveForm extends ActiveForm
{
    #[Override]
    public function configure(): void
    {
        $this->action = '/';
        $this->model = new TestContentFieldModel();
        $this->rows = ['content'];

        parent::configure();
    }
}
