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
use Hirtz\Skeleton\Web\DbSession;
use Hirtz\Skeleton\Web\Sitemap;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Skeleton\Web\View;
use Yii;
use yii\authclient\Collection;
use yii\base\ActionEvent;
use yii\base\InvalidConfigException;
use yii\caching\FileCache;
use yii\console\controllers\MigrateController;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;
use yii\log\FileTarget;
use yii\symfonymailer\Mailer;
use yii\web\JqueryAsset;

/**
 * @property DbManager $authManager
 * @property Connection $db
 * @property I18N $i18n
 * @property Sitemap $sitemap
 * @property UrlManager $urlManager
 * @property View $view
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
        if (!isset($config['basePath'])) {
            throw new InvalidConfigException(self::class . '::$basePath must be defined');
        }

        Yii::$classMap = [...Yii::$classMap, ...ArrayHelper::remove($config, 'classMap', [])];

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
                    'htmlLayout' => '@skeleton/Mail/layouts/html',
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

        $path = "{$config['basePath']}/config/";
        $config = ArrayHelper::merge($core, $config);

        if (is_file($params = $path . 'params.php')) {
            $config['params'] = [...$config['params'] ?? [], ...require ($params)];
        }

        if (is_file($db = $path . 'db.php')) {
            $config['components']['db'] = [...require ($db), ...$config['components']['db']];
        }

        // Make sure the cache prefix via params is applied before application bootstrap, as a DB session might get
        // started which could trigger the database schema cache.
        if ($cacheKeyPrefix = ($config['params']['cacheKeyPrefix'] ?? false)) {
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

    protected function setDefaultUrlManagerRules(): void
    {
        /** @see Module::$alias */
        $alias = rtrim((string)$this->getModules()['admin']['alias'], '/');

        $this->addUrlManagerRules([
            'application-health' => 'health/index',
            'sitemap.xml' => 'sitemap/index',
            "$alias/<module>/<controller>/<view>" => 'admin/<module>/<controller>/<view>',
            "$alias/<controller>/<view>" => 'admin/<controller>/<view>',
            "$alias/<controller>" => 'admin/<controller>',
            "$alias/?" => 'admin/',
        ]);
    }

    public function addUrlManagerRules(array $rules, bool $prepend = false): void
    {
        $component = $this->getComponents()['urlManager'];

        $component['rules'] ??= [];
        $component['rules'] = $prepend ? [...$rules, ...$component['rules']] : [...$component['rules'], ...$rules];

        $this->set('urlManager', $component);
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
