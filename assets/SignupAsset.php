<?php

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Includes the signup.min.js file based on the timezone detected by jstimezonedetect.
 */
class SignupAsset extends AssetBundle
{
    /**
     * @var string[]
     */
    public $depends = [
        JqueryAsset::class,
        TimeZoneDetectAsset::class,
    ];

    /**
     * @var array
     */
    public $js = ['js/signup.min.js'];

    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/signup';
}
