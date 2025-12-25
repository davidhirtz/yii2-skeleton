<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Assets\TinyMceAssetBundle;
use Hirtz\Skeleton\Assets\TinyMceLanguageAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Textarea;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagPlaceholderTrait;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Override;
use Stringable;
use Yii;
use yii\helpers\Inflector;

class TinyMceField extends Field
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    /**
     * @var array containing all TinyMCE options, only set directly to override default behavior.
     */
    public array $clientOptions = [];

    /**
     * @var array|string|null containing TinyMCE content CSS files, if empty, the skin's content CSS file will be used.
     * @link https://www.tiny.cloud/docs/tinymce/6/add-css-options/#content_css
     */
    public array|string|null $contentCss = null;

    /**
     * @var string|null containing additional content CSS styles.
     * @link https://www.tiny.cloud/docs/tinymce/6/add-css-options/#content_style
     */
    public ?string $contentStyle = null;

    /**
     * @var array containing all TinyMCE formats, only set directly to override default behavior.
     */
    public array $formats = [];

    /**
     * @var int the height of the editor in pixels.
     */
    public int $height = 400;

    /**
     * @var string|null the language URL to use, if null, the skin asset URL will be used.
     */
    public ?string $languageUrl = null;

    /**
     * @var array containing all TinyMCE plugins, only set directly to override default behavior.
     */
    public array $plugins = [];

    /**
     * @var string|null|false the TinyMCE skin to use. If the string is a path, it will be resolved to the editor's
     * `skin_url`. If false, no skin will be used.
     * @link https://www.tiny.cloud/docs/tinymce/6/editor-skin/#skin
     */
    public string|null|false $skin = null;

    /**
     * @var array containing all TinyMCE style dropdown options, only set directly to override default behavior.
     */
    public array $stylesFormats = [];

    /**
     * @var array containing all TinyMCE options, only set directly to override default behavior.
     */
    public array $toolbar = [];

    /**
     * @var array|class-string|HtmlValidator|null containing the validator configuration. If set to false, no
     * validation will be performed.
     */
    public array|HtmlValidator|string|null $validator = HtmlValidator::class;

    protected ?string $value = null;

    public function validator(array|HtmlValidator|string|null $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    public function value(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'a-' . uniqid();

        if (!$this->validator instanceof HtmlValidator) {
            $this->validator = $this->validator ? Yii::createObject($this->validator) : null;
        }

        if (null === $this->languageUrl) {
            $bundle = Yii::$app->getAssetManager()->getBundle(TinyMceLanguageAssetBundle::class);
            $this->languageUrl = $bundle->baseUrl;
        }

        $bundle = Yii::$app->getAssetManager()->getBundle(AdminAssetBundle::class);
        $this->contentCss ??= "$bundle->baseUrl/css/wysiwyg.css";

        $this->value ??= $this->model->{$this->property} ?? '';

        $this->setDefaultOptions();
        $this->configureToolbar();
        $this->configurePlugins();

        $this->registerClientScript();

        parent::configure();
    }

    protected function getInput(): string|Stringable
    {
        $content = Textarea::make()
            ->attributes($this->attributes)
            ->attribute('hidden', true)
            ->value($this->value);

        return Html::tag('tinymce-editor', $content, [
            'data-config' => $this->clientOptions,
        ]);
    }

    protected function setDefaultOptions(): void
    {
        //        $this->clientOptions['selector'] ??= '#' . $this->getId();
        $this->clientOptions['promotion'] ??= false;
        $this->clientOptions['statusbar'] ??= false;
        $this->clientOptions['menubar'] ??= false;
        $this->clientOptions['resize'] ??= true;
        $this->clientOptions['paste_block_drop'] ??= false;
        $this->clientOptions['height'] ??= $this->height;
        $this->clientOptions['highlight_on_focus'] ??= true;
        $this->clientOptions['object_resizing'] ??= false;
        $this->clientOptions['convert_urls'] ??= false;
        $this->clientOptions['license_key'] ??= 'gpl';

        if ($this->language !== Yii::$app->sourceLanguage) {
            $this->clientOptions['language'] ??= match ($this->language) {
                'fr' => 'fr_FR',
                'pt' => 'pt_BR',
                'zh-CN' => 'zh_Hans',
                'zh-TW' => 'zh_Hant',
                default => $this->language,
            };

            $this->clientOptions['language_url'] ??= "$this->languageUrl/{$this->clientOptions['language']}.js";
        }

        if ($this->skin) {
            $this->clientOptions[str_contains($this->skin, '/') ? 'skin_url' : 'skin'] ??= $this->skin;
        }

        if ($this->contentCss) {
            $this->clientOptions['content_css'] ??= $this->contentCss;
        }

        if ($this->contentStyle) {
            $this->clientOptions['content_style'] ??= $this->contentStyle;
        }

        if ($allowedElements = ($this->validator?->purifierOptions['HTML.Allowed'] ?? false)) {
            $this->clientOptions['valid_elements'] ??= $allowedElements;
        }

        // Client option `valid_classes` needs every class per tag to be defined on their own.
        if ($this->validator?->allowedClasses) {
            $allowedClasses = [];

            foreach ($this->validator->allowedClasses as $tag => $classes) {
                $allowedClasses[$tag] = [];

                foreach ($classes as $class) {
                    foreach (explode(' ', $class) as $className) {
                        if (!in_array($className, $allowedClasses[$tag], true)) {
                            $allowedClasses[$tag][] = $className;
                        }
                    }
                }
            }

            $this->clientOptions['valid_classes'] ??= $allowedClasses;
        }
    }

    protected function configureToolbar(): void
    {
        if (!$this->toolbar) {
            $this->configureStyleFormats();
            $this->configureStyles();
            $this->configureLinks();
            $this->configureTable();
            $this->configureAdditionalToolbarItems();
        }

        $this->clientOptions['toolbar'] ??= implode(' ', $this->toolbar);
    }

    /**
     * Creates the style dropdown. All classes allowed for inline and block elements will be added to the dropdown. if
     * there are any styles defined, the headline styles will be added also.
     */
    protected function configureStyleFormats(): void
    {
        $headlines = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $tags = [...$headlines, 'p', 'span'];

        foreach ($tags as $tag) {
            if ($this->isTagAllowed($tag)) {
                $this->setStylesFormArray($tag, $this->validator?->allowedClasses[$tag] ?? []);
            }
        }

        $headlineStyles = [];

        foreach ($headlines as $i => $tag) {
            if ($this->isTagAllowed($tag)) {
                if ($this->stylesFormats) {
                    $headlineStyles[] = [
                        'title' => Yii::t('skeleton', 'Heading {n}', ['n' => $i + 1]),
                        'format' => $tag,
                    ];
                } else {
                    $this->toolbar[] = $tag;
                }
            }
        }

        if ($this->stylesFormats) {
            if ($headlineStyles) {
                $this->stylesFormats = [...$headlineStyles, ...$this->stylesFormats];
            }

            $this->clientOptions['style_formats'] ??= $this->stylesFormats;

            $this->toolbar[] = 'styles';
        }

        if ($this->toolbar) {
            $this->toolbar[] = '|';
        }

        if ($this->formats) {
            $this->clientOptions['formats'] ??= $this->formats;
        }

        if ($this->validator) {
            $this->clientOptions['formats']['removeformat'] ??= [
                [
                    'selector' => $this->validator->allowedHtmlTags,
                    'remove' => 'all',
                    'expand' => false,
                    'block_expand' => true,
                ]
            ];
        }
    }

    protected function configureStyles(): void
    {
        $tags = [
            'strong' => 'bold',
            'em' => 'italic',
            'u' => 'underline',
            's' => 'strikethrough',
            'ul' => 'bullist',
            'ol' => 'numlist',
            'blockquote' => 'blockquote',
        ];

        $hasStyles = false;

        foreach ($tags as $tag => $toolbar) {
            if ($this->isTagAllowed($tag)) {
                $this->toolbar[] = $toolbar;
                $hasStyles = true;
            }
        }

        if ($hasStyles) {
            $this->toolbar[] = '|';
        }
    }

    protected function configureLinks(): void
    {
        if ($this->isTagAllowed('a')) {
            $this->toolbar[] = 'link';
            $this->toolbar[] = 'unlink';
            $this->toolbar[] = '|';

            $linkClassList = [];

            foreach ($this->validator?->allowedClasses['a'] ?? [] as $name => $cssClass) {
                if (is_int($name)) {
                    $name = match ($cssClass) {
                        'btn' => Yii::t('skeleton', 'Button'),
                        'cta' => Yii::t('skeleton', 'Call to action'),
                        default => Inflector::humanize($cssClass),
                    };
                }

                $linkClassList[] = [
                    'title' => $name,
                    'value' => $cssClass,
                ];
            }

            if ($linkClassList) {
                array_unshift($linkClassList, [
                    'title' => Yii::t('skeleton', 'None'),
                    'value' => '',
                ]);

                $this->clientOptions['link_class_list'] ??= $linkClassList;
            }
        }
    }

    protected function configureTable(): void
    {
        if ($this->isTagAllowed('table')) {
            $this->toolbar[] = 'table';
            $this->toolbar[] = '|';
        }
    }

    protected function configureAdditionalToolbarItems(): void
    {
        $this->toolbar[] = 'removeformat';
        $this->toolbar[] = 'code';
        $this->toolbar[] = 'fullscreen';
        $this->toolbar[] = '|';
        $this->toolbar[] = 'undo';
        $this->toolbar[] = 'redo';
    }

    protected function configurePlugins(): void
    {
        $toolbars = [
            'code' => 'code',
            'bullist' => 'lists',
            'fullscreen' => 'fullscreen',
            'link' => 'link',
            'numlist' => 'lists',
            'table' => 'table',
        ];

        foreach ($toolbars as $toolbar => $plugin) {
            if (in_array($toolbar, $this->toolbar, true)) {
                $this->plugins[] = $plugin;
            }
        }

        $this->clientOptions['plugins'] ??= implode(' ', array_unique($this->plugins));
    }

    protected function setStylesFormArray(string $tag, array $styles = []): void
    {
        foreach ($styles as $name => $cssClass) {
            if (is_int($name)) {
                $name = Inflector::humanize($cssClass);
            }

            $this->stylesFormats[] = [
                'name' => $cssClass,
                'title' => $name,
                'inline' => $tag,
                'classes' => [$cssClass],
            ];
        }
    }

    protected function isTagAllowed(string $tag): bool
    {
        return !$this->validator || in_array($tag, $this->validator->allowedHtmlTags, true);
    }

    protected function registerClientScript(): void
    {
        $this->view->registerAssetBundle(TinyMceAssetBundle::class);
    }
}
