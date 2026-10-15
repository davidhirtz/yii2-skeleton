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
        $bundles = Yii::$app->getAssetManager()->bundles;
        self::assertIsArray($bundles);
        self::assertArrayHasKey(TinyMceAssetBundle::class, $bundles);
    }

    public function testTheEditorLoadsTheLanguageOfASourceLanguageOtherThanEnglish(): void
    {
        Yii::$app->sourceLanguage = 'de';
        Yii::$app->language = 'de';

        $config = $this->getEditorConfig();

        self::assertSame('de', $config['language'] ?? null);
        self::assertStringEndsWith('/de.js', $config['language_url'] ?? '');
    }

    public function testTheEditorKeepsItsOwnLanguageForEnglish(): void
    {
        Yii::$app->language = 'en-US';

        self::assertArrayNotHasKey('language', $this->getEditorConfig());
    }

    /**
     * @return array<string, mixed>
     */
    private function getEditorConfig(): array
    {
        $form = TestContentFieldActiveForm::make()->render();

        preg_match("/data-config='([^']+)'/", $form, $matches);

        $config = json_decode(html_entity_decode($matches[1] ?? ''), true);
        self::assertIsArray($config);

        return $config;
    }
}

class TestContentFieldModel extends Model
{
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

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return ['content'];
    }
}
