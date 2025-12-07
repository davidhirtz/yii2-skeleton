<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use yii\web\AssetBundle;

class TinyMceAssetBundle extends AssetBundle
{
    public $js = ['tinymce.min.js'];
    public $sourcePath = '@vendor/tinymce/tinymce/';
}
