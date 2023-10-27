<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\TinyMceAssetBundle;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class TinyMceEditor extends InputWidget
{
    /**
     * @var array containing all TinyMCE options, only set directly to override default behavior.
     */
    public $clientOptions = [];

    /**
     * @var array containing all TinyMCE plugins, only set directly to override default behavior.
     */
    public array $plugins = [];

    /**
     * @var array containing all TinyMCE style dropdown options, only set directly to override default behavior.
     */
    public array $stylesFormats = [];

    /**
     * @var array containing all TinyMCE options, only set directly to override default behavior.
     */
    public array $toolbar = [];

    /**
     * @var array|class-string|HtmlValidator|false containing the validator configuration. If set to false, no
     * validation will be performed.
     */
    public $validator = HtmlValidator::class;

    public function init(): void
    {
        if ($this->validator && !$this->validator instanceof HtmlValidator) {
            $this->validator = Yii::createObject($this->validator);
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        $this->configureEditor();
        $this->registerClientScript();
    }

    protected function configureEditor(): void
    {
        $this->setDefaultOptions();
        $this->configureToolbar();
        $this->configurePlugins();
    }

    protected function setDefaultOptions(): void
    {
        $this->clientOptions['selector'] ??= '#' . $this->options['id'];
        $this->clientOptions['promotion'] ??= false;
        $this->clientOptions['statusbar'] ??= false;
        $this->clientOptions['menubar'] ??= false;
        $this->clientOptions['resize'] ??= true;
        $this->clientOptions['paste_block_drop'] ??= false;

        if ($allowedElements = ($this->validator?->purifierOptions['HTML.Allowed'] ?? false)) {
            $this->clientOptions['valid_elements'] ??= $allowedElements;
        }

        if ($this->validator?->allowedClasses) {
            $allowedClasses = [];

            foreach ($this->validator->allowedClasses as $tag => $classes) {
                $allowedClasses[$tag] = array_values($classes);
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

        $this->clientOptions['toolbar'] ??= implode(' ', $this->toolbar);//' table | code | fullscreen';

    }

    /**
     * Creates the style dropdown. All classes allowed for inline and block elements will be added to the dropdown. if
     * there are any styles defined, the headline styles will be added also.
     */
    protected function configureStyleFormats(): void
    {
        $headlines = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $tags = array_merge($headlines, ['p', 'span']);

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
                        'title' => Yii::t('app', 'Heading {n}', ['n' => $i + 1]),
                        'format' => $tag,
                    ];
                } else {
                    $this->toolbar[] = $tag;
                }
            }
        }

        if ($this->stylesFormats) {
            if ($headlineStyles) {
                $this->stylesFormats = array_merge($headlineStyles, $this->stylesFormats);
            }

            $this->clientOptions['style_formats'] ??= $this->stylesFormats;

            $this->toolbar[] = 'styles';
            $this->toolbar[] = '|';
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
            'table' => 'table',
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
                        'btn' => Yii::t('app', 'Button'),
                        'cta' => Yii::t('app', 'Call to action'),
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
                    'title' => Yii::t('app', 'None'),
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
            if (in_array($toolbar, $this->toolbar)) {
                $this->plugins[] = $plugin;
            }
        }

        $this->clientOptions['plugins'] ??= implode(' ', array_unique($this->plugins));
    }

    protected function setStylesFormArray(string $tag, array $styles = []): void
    {
        foreach ($styles ?? [] as $name => $cssClass) {
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
        return !$this->validator || in_array($tag, $this->validator->allowedHtmlTags);
    }

    /**
     * Registers CKEditor plugin.
     */
    protected function registerClientScript(): void
    {
        $view = $this->getView();
        TinyMceAssetBundle::register($view);

        $view->registerJs('tinymce.init(' . Json::encode($this->clientOptions) . ')');
    }
}