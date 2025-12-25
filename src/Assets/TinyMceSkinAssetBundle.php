<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;

class TinyMceSkinAssetBundle extends AssetBundle
{
    public $depends = [TinyMceAssetBundle::class];
    public $sourcePath = '@skeleton/../resources/assets/dist/tinymce/skins';
}
