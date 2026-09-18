<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Debug;

use Hirtz\Skeleton\Assets\EmptyAssetBundle;
use Override;
use Yii;
use yii\grid\GridViewAsset;
use yii\validators\ValidationAsset;
use yii\web\JqueryAsset;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\ActiveFormAsset;
use yii\widgets\PjaxAsset;

/**
 * The debug panels are the one corner of a skeleton application still built on jQuery, which the skeleton
 * itself has left behind: `ApplicationTrait` maps `JqueryAsset` onto an empty bundle, `composer.json` only
 * `provide`s `bower-asset/jquery` instead of installing it, and `Web\View` drops every `POS_READY` script.
 * `yii\debug\Module::beforeAction()` empties `assetManager.bundles`, so the mapping is gone by the time a
 * panel renders and its `GridView` dies publishing the `@bower/jquery/dist` that was never installed.
 */
class Module extends \yii\debug\Module
{
    /**
     * @var list<class-string> the bundles whose scripts throw on load without jQuery. `Web\View` drops the
     *     `POS_READY` calls into them anyway, so nothing is lost by not publishing them.
     */
    private const array JQUERY_BUNDLES = [
        JqueryAsset::class,
        YiiAsset::class,
        GridViewAsset::class,
        ActiveFormAsset::class,
        ValidationAsset::class,
    ];

    /**
     * @var string|null the directory a jQuery for the panels is published from, `null` for none. Suggested
     *     rather than required, so the panels render either way — without it their grid filters do nothing.
     */
    public ?string $jqueryPath = '@vendor/components/jquery';

    public string $jqueryFile = 'jquery.min.js';

    /**
     * Yii derives a module's view path from the directory of its class, which for a subclass is no longer the
     * one holding the views.
     */
    #[Override]
    public function getBasePath(): string
    {
        return Yii::getAlias('@yii/debug');
    }

    #[Override]
    protected function resetGlobalSettings(): void
    {
        parent::resetGlobalSettings();

        // `bower-asset/yii2-pjax` is provided but not installed either, and the only Composer mirror of it
        // drags a jQuery 2.1 along. The timeline panel is the one page wrapping its filter in a `Pjax`;
        // without the plugin that form falls back to a normal submit.
        $bundles = [PjaxAsset::class => ['class' => EmptyAssetBundle::class]];

        if ($this->resolveJqueryPath() !== null) {
            // The panels register their grid filters as `POS_READY` scripts, which `Web\View` throws away.
            Yii::$app->set('view', ['class' => View::class]);

            $bundles[JqueryAsset::class] = [
                'sourcePath' => $this->jqueryPath,
                'js' => [$this->jqueryFile],
                'publishOptions' => ['only' => [$this->jqueryFile]],
            ];
        } else {
            $bundles += array_fill_keys(self::JQUERY_BUNDLES, ['class' => EmptyAssetBundle::class]);
        }

        Yii::$app->getAssetManager()->bundles = $bundles;
    }

    protected function resolveJqueryPath(): ?string
    {
        if ($this->jqueryPath === null) {
            return null;
        }

        $path = Yii::getAlias($this->jqueryPath, false);

        return $path && is_file("$path/$this->jqueryFile") ? $path : null;
    }
}
