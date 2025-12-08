<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;

class TinyMceAssetBundle extends AssetBundle
{
    public $js = ['tinymce.min.js'];
    public $sourcePath = '@vendor/tinymce/tinymce/';
}
