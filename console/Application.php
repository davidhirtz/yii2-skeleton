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
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function preInit(&$config)
    {
        if (!isset($config['basePath'])) {
            $config['basePath'] = getcwd();
        }

        $config = Bootstrap::preInit($config);
        parent::preInit($config);
    }

    /**
     * @inheritdoc
     */
    protected function bootstrap()
    {
        Yii::setAlias('@webroot', '@app/web');
        Yii::setAlias('@web', '@app');

        parent::bootstrap();
    }

    /**
     * @inheritdoc
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