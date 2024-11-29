<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class TinyMceAssetBundle extends AssetBundle
{
    public $sourcePath = '@vendor/tinymce/tinymce/';
    public $js = ['tinymce.min.js'];
}
