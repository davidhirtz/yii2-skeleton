<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\console;

use davidhirtz\yii2\skeleton\base\traits\ApplicationTrait;
use davidhirtz\yii2\skeleton\console\controllers\AssetController;
use davidhirtz\yii2\skeleton\console\controllers\EmailController;
use davidhirtz\yii2\skeleton\console\controllers\MaintenanceController;
use davidhirtz\yii2\skeleton\console\controllers\MessageController;
use davidhirtz\yii2\skeleton\console\controllers\MigrateController;
use davidhirtz\yii2\skeleton\console\controllers\ParamsController;
use davidhirtz\yii2\skeleton\console\controllers\TrailController;
use davidhirtz\yii2\skeleton\console\controllers\UserController;
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
        $this->setDefaultUrlManagerRules();

        parent::bootstrap();
    }

    public function coreCommands(): array
    {
        return array_merge(parent::coreCommands(), [
            'asset' => AssetController::class,
            'email' => EmailController::class,
            'maintenance' => MaintenanceController::class,
            'message' => MessageController::class,
            'migrate' => MigrateController::class,
            'params' => ParamsController::class,
            'trail' => TrailController::class,
            'user' => UserController::class,
        ]);
    }

    protected function setWebrootAliases(): void
    {
        if (!Yii::getAlias('@webroot', false)) {
            Yii::setAlias('@webroot', '@root/web');
        }

        if (!Yii::getAlias('@web', false)) {
            Yii::setAlias('@web', '@root');
        }
    }
}
