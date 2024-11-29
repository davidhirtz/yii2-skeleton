<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class TinyMceLanguageAssetBundle extends AssetBundle
{
    public $sourcePath = '@skeleton/assets/tinymce/langs';

    public $depends = [
        TinyMceAssetBundle::class,
    ];
}
