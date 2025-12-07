<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class TinyMceLanguageAssetBundle extends AssetBundle
{
    public $depends = [TinyMceAssetBundle::class];
    public $sourcePath = '@skeleton/../assets/vendor/tinymce/langs';
}
