<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use yii\web\AssetBundle;
use yii\web\View;

abstract class AbstractAssetBundle extends AssetBundle
{
    public string $filename;

    /**
     * The scripts go into the head rather than after the body, which for a module changes nothing — it is deferred
     * either way — but is what lets the `head-support` extension carry them across an htmx swap. `View::endBody()`
     * renders inside `#wrap`, so a fragment swap that selects less than that — a file upload refreshing its own
     * target, an autocomplete answering with its option list — would drop the bundle of a widget the response
     * introduces.
     */
    public $jsOptions = [
        'position' => View::POS_HEAD,
        'type' => 'module',
    ];

    public $sourcePath = '@skeleton/../resources/assets/dist';
}
