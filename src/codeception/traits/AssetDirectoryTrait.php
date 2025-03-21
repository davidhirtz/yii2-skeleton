<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\codeception\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

trait AssetDirectoryTrait
{
    protected string $assetPath = '@runtime/tests/assets';

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
