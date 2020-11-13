<?php

namespace davidhirtz\yii2\skeleton\composer;

use davidhirtz\yii2\skeleton\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param array $config
     * @return array
     */
    public static function preInit($config)
    {
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
                                'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js',
                            ],
                        ],
                    ],
                ],
                'authClientCollection' => [
                    'class' => 'yii\authclient\Collection',
                ],
                'authManager' => [
                    'class' => 'yii\rbac\DbManager',
                    'cache' => 'cache',
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

        return $config;
    }

    /**
     * Shared application configuration after init.
     * @param Application|\davidhirtz\yii2\skeleton\console\Application $app
     */
    public function bootstrap($app)
    {
        $collection = [];

        if (isset($app->params['facebookClientId']) && isset($app->params['facebookClientSecret'])) {
            $collection['clients']['facebook'] = [
                'class' => 'davidhirtz\yii2\skeleton\auth\clients\Facebook',
                'clientId' => $app->params['facebookClientId'],
                'clientSecret' => $app->params['facebookClientSecret'],
            ];
        }

        if ($collection) {
            $app->setComponents([
                'authClientCollection' => ArrayHelper::merge($collection, $app->getComponents()['authClientCollection']),
            ]);
        }

        if (!isset($app->params['email'])) {
            if ($app instanceof Application) {
                $app->params['email'] = 'hostmaster@' . $app->getRequest()->getServerName();
            }
        }

        $alias = $app->getModules()['admin']['alias'];

        $app->getUrlManager()->addRules([
            '' => $app->defaultRoute,
            'application-health' => 'health/index',
            'sitemap.xml' => 'sitemap/index',
            $alias . '/<module>/<controller>/<view>' => 'admin/<module>/<controller>/<view>',
            $alias . '/<controller>/<view>' => 'admin/<controller>/<view>',
            $alias . '/<controller>' => 'admin/<controller>',
            $alias . '/?' => 'admin/',
        ], false);
    }
}