<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;

class TinyMceAssetBundle extends AssetBundle
{
    public $js = ['tinymce.min.js'];
    public $sourcePath = '@vendor/tinymce/tinymce/';

    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        $bundle = $view->getAssetManager()->getBundle(AdminAssetBundle::class);
        $view->registerCssFile("$bundle->baseUrl/css/tinymce.css");
    }
}
