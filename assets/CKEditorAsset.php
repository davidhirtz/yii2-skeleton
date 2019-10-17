<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class CKEditorAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class CKEditorAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/ckeditor/';

    /**
     * @var array
     */
    public $js = [
        'ckeditor.js',
        'adapters/jquery.js'
    ];

    /**
     * @var array
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset'
    ];
}