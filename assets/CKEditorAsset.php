<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class CKEditorAsset extends AssetBundle
{
    public $sourcePath = '@vendor/tinymce/tinymce';
    public $js = ['tinymce.min.js'];
    public $publishOptions = [
        'only' => [
            'icons/',
            'plugins/',
            'skins/', // TODO can be removed with own skin
            'tinymce.min.js',
        ],
    ];
}