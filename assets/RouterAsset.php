<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class RouterAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class RouterAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@npm/skeleton-router/dist/';

    /**
     * @var array
     */
    public $js = [
        'router.js',
    ];
}