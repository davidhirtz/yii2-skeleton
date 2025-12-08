<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\assets;

use yii\web\AssetBundle;

class TinyMceSkinAssetBundle extends AssetBundle
{
    public $depends = [TinyMceAssetBundle::class];
    public $sourcePath = '@skeleton/../assets/vendor/tinymce/skins';
}
