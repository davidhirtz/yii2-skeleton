<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * @todo
 */
class FileUploadAsset extends AssetBundle
{
    //    /**
    //     * @var string
    //     */
    //    public $sourcePath = '@npm/blueimp-file-upload';
    //
    //    /**
    //     * @var array
    //     */
    //    public $css = [
    //        'css/jquery.fileupload.css'
    //    ];
    //
    //    /**
    //     * @var array
    //     */
    //    public $js = [
    //        'js/jquery.iframe-transport.js',
    //        'js/jquery.fileupload.js'
    //    ];
    //
    //    /**
    //     * @var array
    //     */
    //    public $publishOptions = [
    //        'except' => [
    //            'server/*',
    //            'test'
    //        ],
    //    ];

    /**
     * @var array
     */
    public $depends = [
        AdminAsset::class,
    ];
}
