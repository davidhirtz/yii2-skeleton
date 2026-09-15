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
     * renders inside `#wrap`, so a fragment swap that selects less than that (a form reload, see
     * {@see \Hirtz\Skeleton\Widgets\Forms\Fields\Field::reloadsForm()}) would drop the bundle of a widget the
     * response introduces.
     */
    public $jsOptions = ['type' => 'module', 'position' => View::POS_HEAD];
    public $sourcePath = '@skeleton/../resources/assets/dist';
}
