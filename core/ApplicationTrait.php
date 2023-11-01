<?php

namespace davidhirtz\yii2\skeleton\core;

use Composer\InstalledVersions;
use davidhirtz\yii2\skeleton\auth\clients\Facebook;
use Yii;
use yii\base\ActionEvent;
use yii\base\InvalidConfigException;
use yii\console\controllers\MigrateController;
use yii\helpers\ArrayHelper;

trait ApplicationTrait
{
    protected function preInitInternal(&$config): void
    {
        if (!isset($config['basePath'])) {
            throw new InvalidConfigException(self::class . '::$basePath must be defined');
        }

        Yii::setAlias('@skeleton', dirname(__FILE__, 2));
        Yii::$classMap = array_merge(Yii::$classMap, ArrayHelper::remove($config, 'classMap', []));

        $core = [
            'id' => 'skeleton',
            'aliases' => [
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'bootstrap' => [
                'log',
            ],
            'components' => [
                'assetManager' => [
                    'bundles' => [
                        'yii\bootstrap4\BootstrapAsset' => [
                            'sourcePath' => null,
                            'css' => [],
                        ],
                        'yii\web\JqueryAsset' => [
                            'js' => ['jquery.min.js'],
                        ],
                    ],
                ],
                'authClientCollection' => [
                    'class' => 'yii\authclient\Collection',
                ],
                'authManager' => [
                    'class' => 'davidhirtz\yii2\skeleton\auth\rbac\DbManager',
                ],
                'cache' => [
                    'class' => 'yii\caching\FileCache',
                ],
                'db' => [
                    'class' => 'yii\db\Connection',
                    'enableSchemaCache' => true,
                    'charset' => 'utf8mb4',
                ],
                'i18n' => [
                    'class' => 'davidhirtz\yii2\skeleton\i18n\I18N',
                ],
                'log' => [
                    'traceLevel' => YII_DEBUG ? 3 : 0,
                    'targets' => [
                        [
                            'class' => 'yii\log\FileTarget',
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
                    'class' => 'yii\symfonymailer\Mailer',
                    'htmlLayout' => '@skeleton/mail/layouts/html',
                ],
                'session' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\DbSession',
                ],
                'sitemap' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\Sitemap',
                ],
                'urlManager' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\UrlManager',
                ],
                'view' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\View',
                ],
            ],
            'controllerMap' => [
                'health' => 'davidhirtz\yii2\skeleton\controllers\HealthController',
                'sitemap' => 'davidhirtz\yii2\skeleton\controllers\SitemapController',
            ],
            'modules' => [
                'admin' => [
                    'class' => 'davidhirtz\yii2\skeleton\modules\admin\Module',
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

    /**
     * Sets default url manager rules after configuration.
     */
    protected function setDefaultUrlManagerRules(): void
    {
        $alias = rtrim((string) $this->getModules()['admin']['alias'], '/');

        $this->getUrlManager()->addRules([
            '' => $this->defaultRoute,
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
     * @param array $config
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
        $this->set($id, ArrayHelper::merge($this->getComponents()[$id] ?? [], $definition));
    }

    /**
     * Extends multiple application components.
     */
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