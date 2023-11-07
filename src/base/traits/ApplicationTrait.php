<?php

namespace davidhirtz\yii2\skeleton\base\traits;

use Composer\InstalledVersions;
use davidhirtz\yii2\skeleton\auth\clients\Facebook;
use davidhirtz\yii2\skeleton\controllers\HealthController;
use davidhirtz\yii2\skeleton\controllers\SitemapController;
use davidhirtz\yii2\skeleton\i18n\I18N;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\rbac\DbManager;
use davidhirtz\yii2\skeleton\web\DbSession;
use davidhirtz\yii2\skeleton\web\Sitemap;
use davidhirtz\yii2\skeleton\web\UrlManager;
use davidhirtz\yii2\skeleton\web\View;
use Yii;
use yii\authclient\Collection;
use yii\base\ActionEvent;
use yii\base\InvalidConfigException;
use yii\bootstrap4\BootstrapAsset;
use yii\caching\FileCache;
use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;
use yii\log\FileTarget;
use yii\symfonymailer\Mailer;
use yii\web\JqueryAsset;

trait ApplicationTrait
{
    protected function preInitInternal(&$config): void
    {
        if (!isset($config['basePath'])) {
            throw new InvalidConfigException(self::class . '::$basePath must be defined');
        }

        Yii::$classMap = array_merge(Yii::$classMap, ArrayHelper::remove($config, 'classMap', []));

        $core = [
            'id' => 'skeleton',
            'aliases' => [
                '@root' => $config['basePath'],
                '@skeleton' => dirname(__FILE__, 3),
                '@app' => '@root/app',
                '@config' => '@root/config',
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
                        BootstrapAsset::class => [
                            'sourcePath' => null,
                            'css' => [],
                        ],
                        JqueryAsset::class => [
                            'js' => ['jquery.min.js'],
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
                    'htmlLayout' => '@skeleton/mail/layouts/html',
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
        ];

        if (!YII_ENV_PROD && InstalledVersions::isInstalled('yiisoft/yii2-gii')) {
            $core['bootstrap'][] = 'gii';
            $core['modules']['gii'] = [
                'class' => 'yii\gii\Module',
                'generators' => [
                    'model' => [
                        'class' => 'yii\gii\generators\model\Generator',
                        'baseClass' => 'davidhirtz\yii2\skeleton\db\ActiveRecord',
                        'queryBaseClass' => 'davidhirtz\yii2\skeleton\db\ActiveQuery',
                        'queryNs' => 'app\models\queries',
                        'templates' => [
                            'skeleton' => '@skeleton/gii/generators/model/default',
                        ],
                    ],
                ],
            ];
        }

        $path = $config['basePath'] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
        $config = ArrayHelper::merge($core, $config);

        if (is_file($params = $path . 'params.php')) {
            $config['params'] = array_merge($config['params'] ?? [], require($params));
        }

        if (is_file($db = $path . 'db.php')) {
            $config['components']['db'] = array_merge(require($db), $config['components']['db']);
        }

        // Mailer transport DSN might need to be set via params (eg. yii2-config module)
        if ($mailerDsn = ($config['params']['mailerDsn'] ?? false)) {
            $config['components']['mailer']['transport']['dsn'] = $mailerDsn;
        }

        // Make sure the cache prefix via params is applied before application bootstrap, as a DB session might get
        // started which could trigger the database schema cache.
        if ($cacheKeyPrefix = ($config['params']['cacheKeyPrefix'] ?? false)) {
            $config['components']['cache']['keyPrefix'] = $cacheKeyPrefix;
        }

        $this->setFacebookClientComponent($config);
    }

    protected function setDefaultUrlManagerRules(): void
    {
        $alias = rtrim((string)$this->getModules()['admin']['alias'], '/');

        $this->getUrlManager()->addRules([
            'application-health' => 'health/index',
            'sitemap.xml' => 'sitemap/index',
            "$alias/<module>/<controller>/<view>" => 'admin/<module>/<controller>/<view>',
            "$alias/<controller>/<view>" => 'admin/<controller>/<view>',
            "$alias/<controller>" => 'admin/<controller>',
            "$alias/?" => 'admin/',
        ], false);
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
            $this->on(static::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
                $controller = $event->action->controller;

                if ($controller instanceof MigrateController) {
                    $controller->migrationNamespaces[] = $event->data;
                }
            }, $namespace);
        }
    }
}