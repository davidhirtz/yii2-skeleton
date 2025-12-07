<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

abstract class AbstractAssetBundle extends AssetBundle
{
    public string $filename;
    public $jsOptions = ['type' => 'module'];
    public $sourcePath = '@skeleton/../assets/dist';
}
