<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\console;

use Hirtz\Skeleton\base\traits\ApplicationTrait;
use Hirtz\Skeleton\console\controllers\AssetController;
use Hirtz\Skeleton\console\controllers\EmailController;
use Hirtz\Skeleton\console\controllers\MaintenanceController;
use Hirtz\Skeleton\console\controllers\MessageController;
use Hirtz\Skeleton\console\controllers\MigrateController;
use Hirtz\Skeleton\console\controllers\ParamsController;
use Hirtz\Skeleton\console\controllers\TrailController;
use Hirtz\Skeleton\console\controllers\UserController;
use Yii;

class Application extends \yii\console\Application
{
    use ApplicationTrait;

    public $controllerNamespace = 'app\\commands';

    #[\Override]
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

    #[\Override]
    protected function bootstrap(): void
    {
        $this->setWebrootAliases();
        $this->setDefaultUrlManagerRules();

        parent::bootstrap();
    }

    #[\Override]
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
