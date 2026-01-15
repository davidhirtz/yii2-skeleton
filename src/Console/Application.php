<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console;

use Hirtz\Skeleton\Base\Traits\ApplicationTrait;
use Hirtz\Skeleton\Console\Controllers\AssetController;
use Hirtz\Skeleton\Console\Controllers\EmailController;
use Hirtz\Skeleton\Console\Controllers\MaintenanceController;
use Hirtz\Skeleton\Console\Controllers\MessageController;
use Hirtz\Skeleton\Console\Controllers\MigrateController;
use Hirtz\Skeleton\Console\Controllers\ParamsController;
use Hirtz\Skeleton\Console\Controllers\TrailController;
use Hirtz\Skeleton\Console\Controllers\UserController;
use Override;
use Yii;

class Application extends \yii\console\Application
{
    use ApplicationTrait;

    public $controllerNamespace = 'App\\Commands';

    #[Override]
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

    #[Override]
    protected function bootstrap(): void
    {
        $this->setWebrootAliases();
        $this->setDefaultUrlManagerRules();

        $this->setControllerPath(Yii::getAlias('@app/commands'));

        parent::bootstrap();
    }

    #[Override]
    public function coreCommands(): array
    {
        return [
            ...parent::coreCommands(),
            'asset' => AssetController::class,
            'email' => EmailController::class,
            'maintenance' => MaintenanceController::class,
            'message' => MessageController::class,
            'migrate' => MigrateController::class,
            'params' => ParamsController::class,
            'trail' => TrailController::class,
            'user' => UserController::class,
        ];
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
