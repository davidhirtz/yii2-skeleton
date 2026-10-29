<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;
use yii\web\View;

abstract class AbstractAssetBundle extends AssetBundle
{
    public string $filename;

    /**
     * In the head, so `head-support` carries the scripts across a swap selecting less than `#wrap`, inside which
     * `View::endBody()` renders.
     */
    public $jsOptions = [
        'position' => View::POS_HEAD,
        'type' => 'module',
    ];

    public $sourcePath = '@skeleton/../resources/assets/dist';
}
