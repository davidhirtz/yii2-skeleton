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
 * `yii\debug\Module::beforeAction()` empties `assetManager.bundles`, so the empty `JqueryAsset` mapping is gone and a
 * panel's `GridView` dies publishing the never-installed `@bower/jquery/dist`.
 */
class Module extends \yii\debug\Module
{
    /**
     * Their scripts throw on load without jQuery, and `Web\View` drops their `POS_READY` calls anyway.
     *
     * @var list<class-string>
     */
    private const array JQUERY_BUNDLES = [
        JqueryAsset::class,
        YiiAsset::class,
        GridViewAsset::class,
        ActiveFormAsset::class,
        ValidationAsset::class,
    ];

    /**
     * Suggested rather than required: without it the panels still render, but their grid filters do nothing.
     */
    public ?string $jqueryPath = '@vendor/components/jquery';

    public string $jqueryFile = 'jquery.min.js';

    /**
     * Wildcard patterns of request paths never recorded, among them every path carrying an MCP credential.
     *
     * @var list<string>
     */
    public array $ignoredPaths = [
        '.well-known/appspecific/*',
        'mcp',
        'oauth/token',
        '*/mcp-token/create',
        '*/credential/create',
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
     * Yii derives the view path from the class's directory, which for a subclass no longer holds the views.
     */
    #[Override]
    public function getBasePath(): string
    {
        return Yii::getAlias('@yii/debug');
    }

    /**
     * The toolbar echoes its script inline, which the admin's `Content-Security-Policy` refuses without the nonce.
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
