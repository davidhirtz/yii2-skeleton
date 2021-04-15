<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Class SignupAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class SignupAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/signup';

    /**
     * @link https://github.com/libraryh3lp/jstimezonedetect
     * @var array
     */
    public $js = [
        'https://cdnjs.cloudflare.com/ajax/libs/jstimezonedetect/1.0.6/jstz.min.js',
        YII_DEBUG ? 'js/signup.js' : 'js/signup.min.js',
    ];

    /**
     * @var string[]
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
