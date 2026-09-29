<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Debug;

use Hirtz\Skeleton\Assets\EmptyAssetBundle;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Web\View as WebView;
use Override;
use Yii;
use yii\base\Application;
use yii\grid\GridViewAsset;
use yii\helpers\StringHelper;
use yii\log\Target;
use yii\validators\ValidationAsset;
use yii\web\JqueryAsset;
use yii\web\Request;
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
     * @var list<string> wildcard patterns of request paths never recorded, such as the DevTools probe Chrome
     *     sends on every page with the console open.
     */
    public array $ignoredPaths = [
        '.well-known/appspecific/*',
    ];

    #[Override]
    public function bootstrap($app): void
    {
        parent::bootstrap($app);

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app): void {
            if ($this->logTarget instanceof Target && $this->isIgnoredRequest($app->getRequest())) {
                $this->logTarget->enabled = false;
            }
        });
    }

    /**
     * Yii derives a module's view path from the directory of its class, which for a subclass is no longer the
     * one holding the views.
     */
    #[Override]
    public function getBasePath(): string
    {
        return Yii::getAlias('@yii/debug');
    }

    /**
     * The toolbar echoes its script inline, which the admin's `Content-Security-Policy` refuses without the nonce.
     *
     * @param \yii\base\Event $event
     */
    #[Override]
    public function renderToolbar($event): void
    {
        ob_start();
        parent::renderToolbar($event);
        $html = (string)ob_get_clean();

        $nonce = $event->sender instanceof WebView ? $event->sender->nonce : null;
        echo $nonce ? str_replace('<script>', '<script nonce="' . Html::encode($nonce) . '">', $html) : $html;
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

    protected function isIgnoredRequest(mixed $request): bool
    {
        if (!$request instanceof Request) {
            return false;
        }

        $path = $request->getPathInfo();

        foreach ($this->ignoredPaths as $pattern) {
            if (StringHelper::matchWildcard($pattern, $path)) {
                return true;
            }
        }

        return false;
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
