<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Includes the jstimezonedetect library.
 * @link https://github.com/pellepim/jstimezonedetect
 */
class TimeZoneDetectAsset extends AssetBundle
{
    public $js = ['jstz.min.js'];
    public $sourcePath = '@npm/jstimezonedetect/dist';
}
