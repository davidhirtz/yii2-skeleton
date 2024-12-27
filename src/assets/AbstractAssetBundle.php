<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

abstract class AbstractAssetBundle extends AssetBundle
{
    public string $filename;
    public $sourcePath = '@skeleton/assets/dist';

    public function getModuleUrl(): string
    {
        return $this->baseUrl . '/js/' . $this->filename;
    }

    public static function registerModule(string|array|null $options = null): static
    {
        $view = Yii::$app->getView();
        $asset = static::register($view);
        $view->registerJsModule($asset->getModuleUrl(), $options);

        return $asset;
    }
}
