<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\CKEditorAsset;
use davidhirtz\yii2\skeleton\assets\CKEditorBootstrapAsset;
use davidhirtz\yii2\skeleton\assets\CKEditorExtraAsset;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\AssetBundle;
use yii\widgets\InputWidget;

/**
 * Class CKEditor
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class CKEditor extends InputWidget
{
    /**
     * @var array containing extra toolbar buttons.
     */
    public $toolbar = [];

    /**
     * @var array containing the default toolbar buttons, these will be filtered out by CKEditor's Advanced Content Filter.
     */
    public $defaultToolbar = [
        'Format' => ['h1', 'h2', 'h3', 'h4', 'h5'],
        'Styles' => ['Bold', 'Italic', 'Underline', 'Strike'],
        'List' => ['NumberedList', 'BulletedList', 'Table', 'Blockquote'],
        'Link' => ['Link', 'Unlink'],
        'Extra' => [],
        'Tools' => ['RemoveFormat', 'Source'],
    ];

    /**
     * @var array containing a list of custom plugins that should be added by CKEditor.
     */
    public $extraPlugins = [];

    /**
     * @var array containing a list of plugins that should be removed by CKEditor.
     */
    public $removePlugins = [];

    /**
     * @var array containing a list of custom buttons that should be added by "extra" plugin.
     * Important: Buttons will still be checked against CKEditor's Advanced Content Filter.
     *
     * Optional you can also set the "toolbar" name either as string or as array with the key
     * representing the position and the value the toolbar name. Otherwise, the button has to
     * be added to the toolbar via the config.
     *
     * [
     *   [
     *       'name' => 'Button',
     *       'label' => 'Button label',
     *       'icon' => '/icons/button.svg',
     *       'command' => 'btn',
     *       'definition' => [
     *           'element' => 'a',
     *           'attributes' => [
     *               'class' => 'btn',
     *               'style' => 'color:red;',
     *           ],
     *       ],
     *   ],
     * ]
     */
    public $extraButtons = [];

    /**
     * @var array containing a list of buttons that should be removed regardless of CKEditor's Advanced Content Filter.
     */
    public $removeButtons = [];

    /**
     * @var array containing all CKEditor options, only set directly to override default behavior.
     */
    public $clientOptions = [];

    /**
     * @var string
     */
    public $validator = 'davidhirtz\yii2\skeleton\validators\HtmlValidator';

    /**
     * @var AssetBundle
     */
    public $skinAssetBundle = CKEditorBootstrapAsset::class;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Plugins.
        $extraPlugins = array_unique(array_merge($this->extraPlugins, ['extra']));
        $this->clientOptions['extraPlugins'] = implode(',', $extraPlugins);

        $removePlugins = array_diff(array_unique(array_merge($this->removePlugins, ['elementspath', 'resize'])), $extraPlugins);

        if ($removePlugins) {
            $this->clientOptions['removePlugins'] = implode(',', $removePlugins);
        }

        // Buttons.
        if ($this->removeButtons) {
            $this->clientOptions['removeButtons'] = implode(',', $this->removeButtons);
        }

        if ($this->extraButtons) {
            foreach ($this->extraButtons as &$button) {
                if (!isset($button['name']) || !isset($button['definition']['element'])) {
                    throw new InvalidConfigException('CKEditor buttons require name and element style definition.');
                }

                $button['label'] = $button['label'] ?? '';
                $button['command'] = $button['command'] ?? strtolower($button['name']);
                $button['icon'] = $button['icon'] ?? null;

                if (!isset($button['label'])) {
                    $button['label'] = '';
                }

                if (isset($button['toolbar'])) {
                    if (is_string($button['toolbar'])) {
                        $this->toolbar[$button['toolbar']][] = $button['name'];
                    } elseif (is_array($button['toolbar'])) {
                        array_splice($this->defaultToolbar[current($button['toolbar'])], key($button['toolbar']), 0, [$button['name']]);
                    }
                }
            }
        }

        if ($this->validator) {
            if (is_array($this->validator) && isset($this->validator[0])) {
                $this->validator['class'] = array_shift($this->validator);
            }

            /** @var HtmlValidator $validator */
            $validator = Yii::createObject($this->validator);
            $this->clientOptions['allowedContent'] = str_replace('|', ',', implode(';', array_diff($validator->allowedHtmlTags, ['*[class]'])));

            if ($validator->allowedClasses) {
                $this->clientOptions['allowedContent'] .= ';*(' . implode(',', $validator->allowedClasses) . ')';
            }
        }

        if ($this->skinAssetBundle) {
            $bundle = $this->skinAssetBundle::register($this->getView());
            $this->clientOptions['skin'] = $this->clientOptions['skin'] ?? ('skeleton,' . $bundle->baseUrl . '/');
        }

        $toolbar = [];

        foreach (ArrayHelper::merge($this->defaultToolbar, $this->toolbar) as $name => $items) {
            $toolbar[] = ['name' => $name, 'items' => $items];
        }

        $this->clientOptions['toolbar'] = $toolbar;
        $this->clientOptions['removeDialogTabs'] = $this->clientOptions['removeDialogTabs'] ?? 'link:advanced';
        $this->clientOptions['stylesSet'] = $this->clientOptions['stylesSet'] ?? false;
        $this->clientOptions['customConfig'] = $this->clientOptions['customConfig'] ?? '';
        $this->clientOptions['height'] = $this->clientOptions['height'] ?? 300;


        // Language.
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            $this->clientOptions['language'] = mb_strtolower(Yii::$app->language, Yii::$app->charset);
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        $this->registerPlugin();
    }

    /**
     * Registers CKEditor plugin.
     */
    protected function registerPlugin()
    {
        $options = Json::encode($this->clientOptions);
        $view = $this->getView();

        CKEditorAsset::register($view);
        $extraAsset = CKEditorExtraAsset::register($view);

        $view->registerJs("CKEDITOR.replace('{$this->options['id']}', $options);");
        $view->registerJs("CKEDITOR.plugins.addExternal( 'extra', '{$extraAsset->getPluginPath()}');CKEDITOR.buttons=" . Json::htmlEncode($this->extraButtons), $view::POS_READY, 'ckEditorExtra');
    }
}