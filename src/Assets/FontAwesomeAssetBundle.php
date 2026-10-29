<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;

class FontAwesomeAssetBundle extends AssetBundle
{
    public $css = ['css/all.css'];
    public $sourcePath = '@skeleton/../resources/assets/vendor/fontawesome';
}
