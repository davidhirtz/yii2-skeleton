<?php

namespace davidhirtz\yii2\skeleton\assets\traits;

use Yii;

trait AssetModuleTrait
{
    public $sourcePath = '@skeleton/assets/dist';

    public function getModuleUrl(): string
    {
        return $this->baseUrl . '/js/' . $this->filename;
    }

    public static function registerModule(string|array|null $options = null): static
    {
        $view = Yii::$app->getView();
        $asset = static::register($view);
        $view->registerJsModule($asset->baseUrl . '/' . $asset->filename, $options);

        return $asset;
    }
}
