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

        $core = [
            'id' => 'skeleton',
            'basePath' => dirname(__FILE__, 5),
            'aliases' => [
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'components' => [
                'assetManager' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\AssetManager',
                    'bundles' => [
                        'davidhirtz\yii2\lazysizes\AssetBundle' => [
                            'sourcePath' => null,
                            'js' => [
                                [
                                    '//cdnjs.cloudflare.com/ajax/libs/lazysizes/4.0.4/lazysizes.min.js',
                                    'position' => \davidhirtz\yii2\skeleton\web\View::POS_HEAD,
                                    'async' => true,
                                ],
                            ],
                        ],
                        // Overrides Bootstrap 3 dependency, this can probably removed in the future
                        'dosamigos\fileupload\FileUploadAsset' => [
                            'depends' => [
                                'yii\web\JqueryAsset',
                                'yii\bootstrap4\BootstrapAsset',
                            ],
                        ],
                        'yii\bootstrap4\BootstrapAsset' => [
                            'sourcePath' => null,
                            'css' => [],
                        ],
                        'yii\web\JqueryAsset' => [
                            'sourcePath' => null,
                            'js' => [
                                '//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js',
                            ],
                        ],
                        'yii\jui\JuiAsset' => [
                            'js' => [],
                        ],
                    ],
                    'combineOptions' => [
                        'jsCompressor' => 'gulp combine-js --src {from} --output {to}',
                        'cssCompressor' => 'gulp combine-css --src {from} --output {to}',
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
                            'except' => ['yii\web\HttpException:*'],
                        ],
                    ],
                ],
                'mailer' => [
                    'htmlLayout' => '@skeleton/mail/layouts/html',
                ],
                'session' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\DbSession',
                ],
                'urlManager' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\UrlManager',
                ],
                'user' =>[
                    'class' => 'davidhirtz\yii2\skeleton\web\User',
                ],
                'view' => [
                    'class' => 'davidhirtz\yii2\skeleton\web\View',
                ],
            ],
            'modules' => [
                'admin' => [
                    'class' => 'davidhirtz\yii2\skeleton\modules\admin\Module',
                ],
            ],
        ];

        $basePath = isset($config['basePath']) ? $config['basePath'] : $core['basePath'];

        if (is_file($params = $basePath . '/config/params.php')) {
            $core['params'] = require($params);
        }

        if (is_file($db = $basePath . '/config/db.php')) {
            $core['components']['db'] = array_merge($core['components']['db'], require($db));
        }

        return ArrayHelper::merge($core, $config);
    }

    /**
     * Shared application configuration after init.
     * @param \davidhirtz\yii2\skeleton\web\Application|\davidhirtz\yii2\skeleton\console\Application $app
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
    }
}