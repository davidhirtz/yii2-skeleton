<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;

class TinyMceLanguageAssetBundle extends AssetBundle
{
    public $depends = [TinyMceAssetBundle::class];
    public $sourcePath = '@skeleton/../assets/vendor/tinymce/langs';
}
