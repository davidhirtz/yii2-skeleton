<?php

namespace davidhirtz\yii2\skeleton\codeception\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

trait AssetDirectoryTrait
{
    protected string $assetPath = '@runtime/assets';

    protected function createAssetDirectory(): void
    {
        FileHelper::createDirectory($this->assetPath);
        Yii::$app->getAssetManager()->basePath = Yii::getAlias($this->assetPath);
    }

    protected function removeAssetDirectory(): void
    {
        FileHelper::removeDirectory($this->assetPath);
    }
}
