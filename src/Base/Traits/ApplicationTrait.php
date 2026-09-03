<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use Hirtz\Skeleton\Assets\EmptyAssetBundle;
use Hirtz\Skeleton\Auth\Clients\Facebook;
use Hirtz\Skeleton\Controllers\HealthController;
use Hirtz\Skeleton\Controllers\SitemapController;
use Hirtz\Skeleton\Db\Connection;
use Hirtz\Skeleton\I18n\I18N;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Rbac\DbManager;
use Hirtz\Skeleton\Routing\Compilers\YiiRouteCompiler;
use Hirtz\Skeleton\Routing\Route;
use Hirtz\Skeleton\Routing\RouteCollection;
use Hirtz\Skeleton\Routing\RouteCompilerInterface;
use Hirtz\Skeleton\Routing\UrlGeneratorInterface;
use Hirtz\Skeleton\Web\DbSession;
use Hirtz\Skeleton\Web\Sitemap;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Skeleton\Web\View;
use Yii;
use yii\authclient\Collection;
use yii\base\ActionEvent;
use yii\caching\FileCache;
use yii\console\controllers\MigrateController;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;
use yii\log\FileTarget;
use yii\symfonymailer\Mailer;
use yii\web\JqueryAsset;

/**
 * @property Sitemap $sitemap
 * @property UrlManager $urlManager
 *
 * @method DbManager getAuthManager()
 * @method Connection getDb()
 * @method I18N getI18n()
 * @method Mailer getMailer()
 * @method UrlManager getUrlManager()
 * @method View getView()
 */
trait ApplicationTrait
{
    protected function preInitInternal(&$config): void
    {
        Yii::$classMap = [...Yii::$classMap, ...ArrayHelper::remove($config, 'classMap', [])];
        $this->configuredRoutes = ArrayHelper::remove($config, 'routes', []);

        $core = [
            'id' => 'skeleton',
            'aliases' => [
                '@root' => $config['basePath'],
                '@skeleton' => dirname(__FILE__, 3),
                '@app' => '@root/app',
                '@messages' => '@root/messages',
                '@resources' => '@root/resources',
                '@views' => '@resources/views',
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'bootstrap' => [
                'log',
            ],
            'components' => [
                'assetManager' => [
                    'bundles' => [
                        JqueryAsset::class => [
                            'class' => EmptyAssetBundle::class,
                        ],
                    ],
                ],
                'authClientCollection' => [
                    'class' => Collection::class,
                ],
                'authManager' => [
                    'class' => DbManager::class,
                ],
                'cache' => [
                    'class' => FileCache::class,
                ],
                'db' => [
                    'class' => Connection::class,
                    'enableSchemaCache' => true,
                    'charset' => 'utf8mb4',
                ],
                'i18n' => [
                    'class' => I18N::class,
                    'translations' => [
                        'app' => [
                            'class' => PhpMessageSource::class,
                            'sourceLanguage' => 'en-US',
                            'basePath' => '@messages',
                        ],
                    ],
                ],
                'log' => [
                    'traceLevel' => YII_DEBUG ? 3 : 0,
                    'targets' => [
                        [
                            'class' => FileTarget::class,
                            'levels' => ['error', 'warning'],
                            'fileMode' => 0770, // Make sure both web and console user can write to file
                            'maskVars' => [
                                '_SERVER.HTTP_AUTHORIZATION',
                                '_SERVER.PHP_AUTH_USER',
                                '_SERVER.PHP_AUTH_PW',
                                '_POST.Login.password',
                                '_POST.PasswordResetForm.newPassword',
                                '_POST.PasswordResetForm.repeatPassword',
                                '_POST.SignupForm.password',
                                '_POST.User.newPassword',
                                '_POST.User.oldPassword',
                                '_POST.User.password',
                                '_POST.User.repeatPassword',
                            ],
                            'except' => [
                                'yii\web\HttpException:*',
                            ],
                        ],
                    ],
                ],
                'mailer' => [
                    'class' => Mailer::class,
                    'htmlLayout' => '@skeleton/../resources/mail/layouts/html',
                    'useFileTransport' => YII_DEBUG,
                ],
                'session' => [
                    'class' => DbSession::class,
                ],
                'sitemap' => [
                    'class' => Sitemap::class,
                ],
                'urlManager' => [
                    'class' => UrlManager::class,
                ],
                'view' => [
                    'class' => View::class,
                ],
            ],
            'controllerMap' => [
                'health' => HealthController::class,
                'sitemap' => SitemapController::class,
            ],
            'modules' => [
                'admin' => [
                    'class' => Module::class,
                    'alias' => 'admin',
                    'viewPath' => '@app/modules/admin/views',
                ],
            ],
            'viewPath' => '@views',
        ];

        $config = ArrayHelper::merge($core, $config);
        $path = "{$config['basePath']}/config/";

        if (!YII_ENV_TEST) {
            $file = "{$path}params.php";

            if (is_file($file)) {
                $config['params'] = [...$config['params'] ?? [], ...require($file)];
            }
        }

        $file = "{$path}db.php";

        if (is_file($file)) {
            $config['components']['db'] = [...require($file), ...$config['components']['db']];
        }

        // Make sure the cache prefix via params is applied before application bootstrap, as a DB session might get
        // started which could trigger the database schema cache.
        $cacheKeyPrefix = $config['params']['cacheKeyPrefix'] ?? null;

        if ($cacheKeyPrefix) {
            $config['components']['cache']['keyPrefix'] = $cacheKeyPrefix;
        }

        $this->setDefaultMailerDsn($config);
        $this->setFacebookClientComponent($config);
    }

    protected function setDefaultMailerDsn(&$config): void
    {
        if (!empty($config['components']['mailer']['useFileTransport'])) {
            return;
        }

        if (isset($config['components']['mailer']['transport']) && !is_array($config['components']['mailer']['transport'])) {
            return;
        }

        $config['components']['mailer']['transport']['dsn'] = $config['params']['mailerDsn']
            ?? $config['components']['mailer']['transport']['dsn']
            ?? 'sendmail://default';
    }

    private ?RouteCollection $routes = null;

    private ?RouteCompilerInterface $routeCompiler = null;

    /**
     * @var list<Route>
     */
    private array $configuredRoutes = [];

    protected function setDefaultUrlManagerRules(): void
    {
        /** @see Module::$alias */
        $alias = rtrim((string)$this->getModules()['admin']['alias'], '/');

        $this->addRoutes(
            Route::to('application-health', 'health/index')->name('health'),
            Route::to('sitemap.xml', 'sitemap/index')->name('sitemap'),
            Route::to("$alias/{module}/{controller}/{view}", 'admin/{module}/{controller}/{view}'),
            Route::to("$alias/{controller}/{view}", 'admin/{controller}/{view}'),
            Route::to("$alias/{controller}", 'admin/{controller}'),
            Route::raw("$alias/?", 'admin/'),
        );
    }

    /**
     * Registers routes declared under the application's `routes` config key, after all bundle
     * bootstraps have run so a project's routes are registered last and sort against them by position.
     */
    protected function addConfiguredRoutes(): void
    {
        $this->addRoutes(...$this->configuredRoutes);
    }

    /**
     * The only way to register routes. Array URL rules stay supported through the `urlManager`
     * component's own `rules` config option; both interleave by position in {@see UrlManager::buildRules()}.
     */
    public function addRoutes(Route ...$routes): void
    {
        $added = $this->getRoutes()->add(...$routes);

        if ($added === []) {
            return;
        }

        $component = $this->getComponents()['urlManager'];
        $component['rules'] = [...$component['rules'] ?? [], ...$this->getRouteCompiler()->compile(...$added)];

        $this->set('urlManager', $component);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes ??= Yii::createObject(RouteCollection::class);
    }

    public function getRouteCompiler(): RouteCompilerInterface
    {
        return $this->routeCompiler ??= Yii::createObject(YiiRouteCompiler::class);
    }

    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->getUrlManager();
    }

    /**
     * Detects Facebook client via config.
     */
    protected function setFacebookClientComponent(array &$config): void
    {
        if (isset($config['params']['facebookClientId'], $config['params']['facebookClientSecret'])) {
            $config['components']['authClientCollection']['clients']['facebook'] = [
                'class' => Facebook::class,
            ];
        }
    }

    /**
     * Extends given application component.
     */
    public function extendComponent(string $id, array $definition): void
    {
        $this->set($id, ArrayHelper::merge($definition, $this->getComponents()[$id] ?? []));
    }

    public function extendComponents(array $components): void
    {
        foreach ($components as $id => $definition) {
            $this->extendComponent($id, $definition);
        }
    }

    public function extendModule(string $id, array $module): void
    {
        if ($module) {
            $this->setModule($id, ArrayHelper::merge($module, $this->getModules()[$id] ?? []));
        }
    }

    public function extendModules(array $modules): void
    {
        foreach ($modules as $id => $config) {
            $this->extendModule($id, $config);
        }
    }

    public function setMigrationNamespace(string $namespace): void
    {
        if ($this->getRequest()->getIsConsoleRequest()) {
            $this->on(static::EVENT_BEFORE_ACTION, function (ActionEvent $event): void {
                $controller = $event->action->controller;

                if ($controller instanceof MigrateController) {
                    $controller->migrationNamespaces[] = $event->data;
                }
            }, $namespace);
        }
    }
}
