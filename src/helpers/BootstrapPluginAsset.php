<?php

namespace yii\bootstrap;

use yii\web\AssetBundle;

/**
 * Class BootstrapPluginAsset.
 *
 * Temporary fix to load latest Bootstrap 3 javascript for debug module
 * while using Bootstrap 4 for the main site.
 */
class BootstrapPluginAsset extends AssetBundle
{
    public $sourcePath = null;

    public $js = [
        '//stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
