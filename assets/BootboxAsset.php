<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class BootboxAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class BootboxAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@bower/bootbox/src';

    /**
     * @var array
     */
    public $js = [
        'bootbox.js',
    ];
}