<?php

namespace davidhirtz\yii2\skeleton\console;

use davidhirtz\yii2\skeleton\core\ApplicationTrait;
use Yii;

/**
 * Class Application
 * @package davidhirtz\yii2\skeleton\console
 */
class Application extends \yii\console\Application
{
    use ApplicationTrait;

    /**
     * @var string the namespace that command controller classes are located in.
     */
    public $controllerNamespace = 'app\\commands';

    /**
     * @param array $config
     */
    public function preInit(&$config)
    {
        $config['basePath'] = $config['basePath'] ?? getcwd();
        $this->preInitInternal($config);

        // Removes web components.
        unset(
            $config['components']['errorHandler']['errorAction'],
            $config['components']['user'],
            $config['components']['session']
        );

        parent::preInit($config);
    }

    /**
     * @inheritDoc
     */
    protected function bootstrap()
    {
        $this->setWebrootAliases();
        $this->setDefaultUrlManagerRules();

        parent::bootstrap();
    }

    /**
     * @inheritDoc
     */
    public function coreCommands()
    {
        return array_merge(parent::coreCommands(), [
            'asset' => 'davidhirtz\yii2\skeleton\console\controllers\AssetController',
            'migrate' => 'davidhirtz\yii2\skeleton\console\controllers\MigrateController',
            'params' => 'davidhirtz\yii2\skeleton\console\controllers\ParamsController',
            'trail' => 'davidhirtz\yii2\skeleton\console\controllers\TrailController',
        ]);
    }

    /**
     * Sets webroot aliases for console applications.
     */
    protected function setWebrootAliases()
    {
        if (!Yii::getAlias('@webroot', false)) {
            Yii::setAlias('@webroot', '@app/web');
        }

        Yii::setAlias('@web', '@app');
    }
}