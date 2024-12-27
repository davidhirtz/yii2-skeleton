<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use davidhirtz\yii2\skeleton\assets\traits\AssetModuleTrait;
use yii\web\AssetBundle;

class SignupAssetBundle extends AssetBundle
{
    use AssetModuleTrait;

    public string $filename = 'signup.js';
}
