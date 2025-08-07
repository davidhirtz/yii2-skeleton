<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\bootstrap4\BootstrapAsset;
use yii\web\AssetBundle;

class FileUploadAsset extends AssetBundle
{
    public $css = ['css/jquery.fileupload.css'];

    public $depends = [
        JuiAsset::class,
        BootstrapAsset::class,
    ];

    public $js = [
        'js/jquery.iframe-transport.js',
        'js/jquery.fileupload.js'
    ];

    public $publishOptions = [
        'except' => [
            'server/*',
            'test'
        ],
    ];

    public $sourcePath = '@npm/blueimp-file-upload';
}
