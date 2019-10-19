<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class CKEditorExtraAsset
 * @package davidhirtz\yii2\skeleton\assets
 */
class CKEditorExtraAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/ckeditor-extra/';

    /**
     * @return string
     */
    public function getPluginPath()
    {
        return $this->baseUrl . '/js/' . (YII_DEBUG ? 'plugin.js' : 'plugin.min.js');
    }
}