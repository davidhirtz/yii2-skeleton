<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class JuiAsset extends AssetBundle
{
    public $depends = [JqueryAsset::class];
    public $js = [YII_DEBUG ? 'jquery-ui.js' : 'jquery-ui.min.js'];
    public $sourcePath = '@skeleton/assets/jui/';
}
