<?php

namespace davidhirtz\yii2\skeleton\console;

use davidhirtz\yii2\skeleton\composer\Bootstrap;
use Yii;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\console
 */
class Application extends \yii\console\Application
{
    /**
     * @var string the namespace that command controller classes are located in.
     */
    public $controllerNamespace = 'app\\commands';

    /**
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function preInit(&$config)
    {
        if (!isset($config['basePath'])) {
            $config['basePath'] = getcwd();
        }

        $config = Bootstrap::preInit($config);

        // Removes web components.
        unset($config['components']['errorHandler']['errorAction']);
        unset($config['components']['user']);
        unset($config['components']['session']);

        parent::preInit($config);
    }

    /**
     * @inheritDoc
     */
    protected function bootstrap()
    {
        Yii::setAlias('@webroot', '@app/web');
        Yii::setAlias('@web', '@app');

        parent::bootstrap();
    }

    /**
     * @inheritDoc
     */
    public function coreCommands()
    {
        return array_merge(parent::coreCommands(), [
            'asset' => 'davidhirtz\yii2\skeleton\console\controllers\AssetController',
            'params' => 'davidhirtz\yii2\skeleton\console\controllers\ParamsController',
            'migrate' => [
                'class' => 'davidhirtz\yii2\skeleton\console\controllers\MigrateController',
                'migrationPath' => null,
                'migrationNamespaces' => [
                    'app\migrations',
                    'davidhirtz\yii2\skeleton\migrations',
                ],
            ],
        ]);
    }
}