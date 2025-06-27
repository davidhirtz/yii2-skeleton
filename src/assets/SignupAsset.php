<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Includes the signup.min.js file based on the timezone detected by jstimezonedetect.
 */
class SignupAsset extends AssetBundle
{
    public $depends = [
        JqueryAsset::class,
        TimeZoneDetectAsset::class,
    ];

    public $js = ['js/signup.min.js'];
    public $sourcePath = '@skeleton/assets/signup';
}
