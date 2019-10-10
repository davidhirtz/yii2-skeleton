<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\assets\CKEditorBootstrapAsset;
use davidhirtz\yii2\skeleton\modules\admin\widgets\WidgetConfigTrait;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;

/**
 * Class CKEditor.
 * @package davidhirtz\yii2\skeleton\widgets\form
 */
class CKEditor extends \dosamigos\ckeditor\CKEditor
{
    use WidgetConfigTrait;

    /**
     * @var array
     */
    public $toolbar = [
        ['Bold', 'Italic', 'Underline', 'Strike'],
        ['NumberedList', 'BulletedList', 'Table', 'Blockquote'],
        ['RemoveFormat'],
        ['Link', 'Unlink'],
        ['Source'],
    ];

    /**
     * @inherit
     */
    public $clientOptions = [
        'height' => 300,
        'removeDialogTabs' => 'link:advanced',
    ];

    /**
     * @var array
     */
    public $extraPlugins = [];

    /**
     * @var array
     */
    public $removePlugins = [];

    /**
     * @var string
     */
    public $preset = 'custom';

    /**
     * @var string
     */
    public $validator = 'davidhirtz\yii2\skeleton\validators\HtmlValidator';

    /**
     * @var array containing format tags for the format dropdown.
     */
    public $formatTags;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Plugins.
        $removePlugins = array_merge($this->removePlugins, [
            'elementspath',
            'magicline',
            'resize',
            'contextmenu',
            'liststyle',
            'tabletools',
            'tableselection',
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
            $this->clientOptions['allowedContent'] = str_replace('|', ',', implode(';', $validator->allowedHtmlTags));

            // Format dropdown.
            if ($formatTags = $this->formatTags ?: array_intersect($validator->allowedHtmlTags, ['h1', 'h2', 'h3', 'h4', 'h5', 'code'])) {
                array_unshift($this->toolbar, ['Format']);
                array_unshift($formatTags, 'p');
                $this->clientOptions['format_tags'] = implode(';', array_unique($formatTags));
            }
        }

        $this->clientOptions['removePlugins'] = implode(',', array_unique(array_filter($removePlugins)));
        $this->clientOptions['toolbar'] = $this->toolbar;

        // Editor skin path.
        $bundle = CKEditorBootstrapAsset::register($view = $this->getView());
        $this->clientOptions['skin'] = 'bootstrap,' . $bundle->baseUrl . '/';

        // Contents CSS file.
        $bundle = $view->registerAssetBundle($bundle->editorAssetBundle ?: 'davidhirtz\yii2\skeleton\assets\AdminAsset');
        $this->clientOptions['contentsCss'] = $bundle->baseUrl . '/' . $bundle->css[0];

        // Language.
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            $this->clientOptions['language'] = Yii::$app->language;
        }

        parent::init();
    }
}