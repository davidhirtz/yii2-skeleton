<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Includes FontAwesome 6.7.2 CSS and WOFF2 files, everything es is excluded.
 */
class FontAwesomeAsset extends AssetBundle
{
    public $css = ['css/all.css'];
    public $sourcePath = '@skeleton/assets/fontawesome';
}
