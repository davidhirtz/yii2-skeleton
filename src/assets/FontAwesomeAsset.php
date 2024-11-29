<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

/**
 * Includes FontAwesome 5.15.4 CSS and WOFF2 files, everything es is excluded.
 */
class FontAwesomeAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $css = ['css/all.css'];

    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/fontawesome';
}
