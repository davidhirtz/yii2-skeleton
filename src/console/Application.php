<?php

namespace davidhirtz\yii2\skeleton\console;

use davidhirtz\yii2\skeleton\base\traits\ApplicationTrait;
use davidhirtz\yii2\skeleton\console\controllers\AssetController;
use davidhirtz\yii2\skeleton\console\controllers\MaintenanceController;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
use davidhirtz\yii2\skeleton\console\controllers\ParamsController;
use davidhirtz\yii2\skeleton\console\controllers\TrailController;
use Yii;

class Application extends \yii\console\Application
{
    use ApplicationTrait;

    public $controllerNamespace = 'app\\commands';

    public function preInit(&$config): void
    {
        $config['basePath'] ??= getcwd();
        $this->preInitInternal($config);

        unset(
            $config['components']['errorHandler']['errorAction'],
            $config['components']['user'],
            $config['components']['session']
        );

        parent::preInit($config);
    }

    protected function bootstrap(): void
    {
        $this->setWebrootAliases();

        parent::bootstrap();

        $this->setDefaultUrlManagerRules();
    }

    public function coreCommands(): array
    {
        return array_merge(parent::coreCommands(), [
            'asset' => AssetController::class,
            'migrate' => MigrateController::class,
            'maintenance' => MaintenanceController::class,
            'params' => ParamsController::class,
            'trail' => TrailController::class,
        ]);
    }

    protected function setWebrootAliases(): void
    {
        if (!Yii::getAlias('@webroot', false)) {
            Yii::setAlias('@webroot', '@root/web');
        }

        Yii::setAlias('@web', '@root');
    }
}
