<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\TinyMceAssetBundle;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;
use Yii;
use yii\base\Model;

class TinyMceFieldTest extends TestCase
{
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
