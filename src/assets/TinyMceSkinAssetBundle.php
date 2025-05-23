<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class TinyMceSkinAssetBundle extends AssetBundle
{
    public $sourcePath = '@skeleton/assets/tinymce/skins';

    public $depends = [
        TinyMceAssetBundle::class,
    ];
}
