<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\codeception\functional;

use Hirtz\Skeleton\codeception\traits\AssetDirectoryTrait;

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
