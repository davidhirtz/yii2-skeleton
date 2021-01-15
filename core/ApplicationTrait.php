<?php

namespace davidhirtz\yii2\skeleton\core;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Class ApplicationTrait
 * @package davidhirtz\yii2\skeleton\core
 */
trait ApplicationTrait
{
    /**
     * @param array $config
     */
    protected function preInitInternal(&$config)
    {
        if (!isset($config['basePath'])) {
            throw new InvalidConfigException(__CLASS__ . '::$basePath must be defined');
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
                    'class' => 'davidhirtz\yii2\skeleton\web\AssetManager',
                    'bundles' => [
                        'yii\bootstrap4\BootstrapAsset' => [
                            'sourcePath' => null,
                            'css' => [],
                        ],
                        'yii\web\JqueryAsset' => [
                            'sourcePath' => null,
                            'js' => [
                                'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js',
                            ],
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
                            'except' => [
                                'yii\web\HttpException:*',
                            ],
                        ],
                    ],
                ],
                'mailer' => [
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

        if (YII_DEBUG) {
            $core['bootstrap'][] = 'debug';
            $core['modules']['debug'] = [
                'class' => 'yii\debug\Module',
                'traceLine' => '<a href="phpstorm://open?url={file}&line={line}">{file}:{line}</a>',
            ];
        }

        if (YII_ENV_DEV) {
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
            $config['components']['db'] = array_merge($config['components']['db'], require($db));
        }

        $this->setFacebookClientComponent($config);
    }

    /**
     * Sets default url manager rules after configuration.
     */
    protected function setDefaultUrlManagerRules()
    {
        $alias = rtrim($this->getModules()['admin']['alias'], '/');

        $this->getUrlManager()->addRules([
            '' => $this->defaultRoute,
            'application-health' => 'health/index',
            'sitemap.xml' => 'sitemap/index',
            "{$alias}/<module>/<controller>/<view>" => 'admin/<module>/<controller>/<view>',
            "{$alias}/<controller>/<view>" => 'admin/<controller>/<view>',
            "{$alias}/<controller>" => 'admin/<controller>',
            "{$alias}/?" => 'admin/',
        ], false);
    }

    /**
     * Detects Facebook client via config.
     * @param array $config
     */
    protected function setFacebookClientComponent(&$config)
    {
        if (isset($config['params']['facebookClientId'], $config['params']['facebookClientSecret'])) {
            $config['components']['authClientCollection']['clients']['facebook'] = [
                'class' => 'davidhirtz\yii2\skeleton\auth\clients\Facebook',
            ];
        }
    }
}