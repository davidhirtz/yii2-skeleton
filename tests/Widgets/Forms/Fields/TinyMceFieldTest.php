<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\TinyMceAssetBundle;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
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

    public function testTheChineseLanguagesLoadTheShippedFiles(): void
    {
        Yii::$app->language = 'zh-CN';
        $config = $this->getEditorConfig();

        self::assertSame('zh-Hans', $config['language'] ?? null);
        self::assertFileExists(dirname(__DIR__, 4) . '/resources/assets/vendor/tinymce/langs/zh-Hans.js');
        self::assertStringEndsWith('/zh-Hans.js', $config['language_url'] ?? '');

        Yii::$app->language = 'zh-TW';
        self::assertStringEndsWith('/zh-Hant.js', $this->getEditorConfig()['language_url'] ?? '');
    }

    public function testTheEditorKeepsItsOwnLanguageForEnglish(): void
    {
        Yii::$app->language = 'en-US';

        self::assertArrayNotHasKey('language', $this->getEditorConfig());
    }

    public function testTheStylesMenuDoesNotPreviewHeadings(): void
    {
        $formats = $this->getEditorConfig(TestStyledContentFieldActiveForm::class)['style_formats'] ?? [];
        self::assertIsArray($formats);

        $heading = $formats[0] ?? self::fail('No style formats.');
        self::assertIsArray($heading);
        self::assertSame('h2', $heading['block'] ?? null);
        self::assertFalse($heading['preview'] ?? null);
    }

    public function testTheContentStyleRendersAnArrayAsCss(): void
    {
        Yii::$container->set(TinyMceField::class, [
            'contentStyle' => [
                '@import url("fonts.css");',
                'body' => ['font-family' => '"Inter", sans-serif', 'font-size' => '1rem'],
                'h1, h2' => 'font-weight: 700',
            ],
        ]);

        try {
            $config = $this->getEditorConfig();
        } finally {
            Yii::$container->clear(TinyMceField::class);
        }

        self::assertSame(
            "@import url(\"fonts.css\");\n"
            . "body { font-family: \"Inter\", sans-serif; font-size: 1rem }\n"
            . 'h1, h2 { font-weight: 700 }',
            $config['content_style'] ?? null,
        );
    }

    /**
     * @param class-string<TestContentFieldActiveForm> $formClass
     * @return array<string, mixed>
     */
    private function getEditorConfig(string $formClass = TestContentFieldActiveForm::class): array
    {
        $form = $formClass::make()->render();

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

class TestStyledContentFieldModel extends TestContentFieldModel
{
    #[Override]
    public function rules(): array
    {
        return [
            [
                ['content'],
                HtmlValidator::class,
                'allowedHtmlTags' => ['h2', 'p'],
                'allowedClasses' => ['p' => ['lead']],
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
        $this->model ??= new TestContentFieldModel();

        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return ['content'];
    }
}

class TestStyledContentFieldActiveForm extends TestContentFieldActiveForm
{
    #[Override]
    public function configure(): void
    {
        $this->model = new TestStyledContentFieldModel();
        parent::configure();
    }
}
