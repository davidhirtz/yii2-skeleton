<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\codeception\functional;

use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;

abstract class BaseCest
{
    use AssetDirectoryTrait;

    public function _before(): void
    {
        $this->createAssetDirectory();
    }

    public function _after(): void
    {
        $this->removeAssetDirectory();
    }
}
