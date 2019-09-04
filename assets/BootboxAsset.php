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
    public $sourcePath = '@bower/bootbox/dist';

    /**
     * @var array
     */
    public $js = [
        'bootbox.min.js',
    ];
}