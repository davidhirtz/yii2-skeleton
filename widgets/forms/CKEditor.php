<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\CKEditorAsset;
use davidhirtz\yii2\skeleton\assets\CKEditorBootstrapAsset;
use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\AssetBundle;
use yii\widgets\InputWidget;

/**
 * Class CKEditor.
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class CKEditor extends InputWidget
{
    use WidgetConfigTrait;

    /**
     * @var array
     */
    public $toolbar = [
        ['h1', 'h2', 'h3', 'h4', 'h5'],
        ['Bold', 'Italic', 'Underline', 'Strike'],
        ['NumberedList', 'BulletedList', 'Table', 'Blockquote'],
        ['RemoveFormat'],
        ['Link', 'Unlink'],
        ['Source'],
    ];

    /**
     * @inherit
     */
    public $clientOptions = [];

    /**
     * @var array
     */
    public $extraPlugins = [];

    /**
     * @var array
     */
    public $removePlugins = [];

    /**
     * @var array
     */
    public $removeButtons = [];

    /**
     * @var string
     */
    public $validator = 'davidhirtz\yii2\skeleton\validators\HtmlValidator';

    /**
     * @var array containing format tags for the format dropdown.
     */
    public $formatTags;

    /**
     * @var AssetBundle
     */
    public $skinAssetBundle = CKEditorBootstrapAsset::class;

    /**
     * @var
     */
    private static $isRegistered;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Plugins.
        $removePlugins = array_merge($this->removePlugins, [
            'elementspath',
            'resize',
        ]);

        if ($this->extraPlugins) {
            $removePlugins = array_diff($removePlugins, $this->extraPlugins);
        }

        if ($this->validator) {
            if (is_array($this->validator) && isset($this->validator[0])) {
                $this->validator['class'] = array_shift($this->validator);
            }

            /** @var HtmlValidator $validator */
            $validator = Yii::createObject($this->validator);
            $this->clientOptions['allowedContent'] = str_replace('|', ',', implode(';', array_diff($validator->allowedHtmlTags, ['*[class]'])));

            // Styles.
            if (!isset($this->clientOptions['stylesSet'])) {
                $this->clientOptions['stylesSet'] = false;
            }

            if ($this->clientOptions['stylesSet']) {
                array_unshift($this->toolbar, ['Styles']);
            }

            // Format dropdown.
            if ($this->formatTags !== false) {
                if ($formatTags = $this->formatTags ?: array_intersect($validator->allowedHtmlTags, ['h1', 'h2', 'h3', 'h4', 'h5', 'code'])) {
                    array_unshift($this->toolbar, ['Format']);
                    array_unshift($formatTags, 'p');

                    $this->clientOptions['format_tags'] = implode(';', array_unique($formatTags));
                }
            }

            if ($validator->allowedClasses) {
                $this->clientOptions['allowedContent'] .= ';*(' . implode(',', $validator->allowedClasses) . ')';
            }
        }

        if($this->skinAssetBundle) {
            $bundle = $this->skinAssetBundle::register($view = $this->getView());

            if (!isset($this->clientOptions['skin'])) {
                $this->clientOptions['skin'] = 'skeleton,' . $bundle->baseUrl . '/';
            }
        }

        if ($removePlugins = array_unique(array_filter($removePlugins))) {
            $this->clientOptions['removePlugins'] = implode(',', $removePlugins);
        }

        if ($this->extraPlugins) {
            $this->clientOptions['extraPlugins'] = implode(',', $this->extraPlugins);
        }

        if ($this->removeButtons) {
            $this->clientOptions['removeButtons'] = implode(',', $this->removeButtons);
        }

        $this->clientOptions['toolbar'] = $this->toolbar;

        if (!isset($this->clientOptions['removeDialogTabs'])) {
            $this->clientOptions['removeDialogTabs'] = 'link:advanced';
        }

        if (!isset($this->clientOptions['customConfig'])) {
            $this->clientOptions['customConfig'] = '';
        }

        if (!isset($this->clientOptions['height'])) {
            $this->clientOptions['height'] = 300;
        }

        // Language.
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            $this->clientOptions['language'] = Yii::$app->language;
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

        $view->registerJs("CKEDITOR.replace('{$this->options['id']}', $options);");
        CKEditorAsset::register($view);
    }
}