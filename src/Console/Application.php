<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console;

use Hirtz\Skeleton\Base\Traits\ApplicationTrait;
use Hirtz\Skeleton\Console\Controllers\AssetController;
use Hirtz\Skeleton\Console\Controllers\EmailController;
use Hirtz\Skeleton\Console\Controllers\HelpController;
use Hirtz\Skeleton\Console\Controllers\MaintenanceController;
use Hirtz\Skeleton\Console\Controllers\MessageController;
use Hirtz\Skeleton\Console\Controllers\MigrateController;
use Hirtz\Skeleton\Console\Controllers\ParamsController;
use Hirtz\Skeleton\Console\Controllers\RedirectController;
use Hirtz\Skeleton\Console\Controllers\SearchController;
use Hirtz\Skeleton\Console\Controllers\TrailController;
use Hirtz\Skeleton\Console\Controllers\UpgradeController;
use Hirtz\Skeleton\Console\Controllers\UserController;
use Hirtz\Skeleton\Console\Controllers\UserLoginController;
use Hirtz\Skeleton\Console\Controllers\UploadController;
use Hirtz\Skeleton\Console\Controllers\UserTokenController;
use Override;
use Yii;
use yii\base\InvalidCallException;

class Application extends \yii\console\Application
{
    use ApplicationTrait;

    public $controllerNamespace = 'App\\Commands';

    /**
     * The mirror of `Web\Application::current()`, for code that only ever runs as a command. `Yii::$app` stays
     * the union of the two, which is what makes a console command reaching for `getUser()` or `getSession()` a
     * static analysis error rather than a runtime surprise.
     */
    public static function current(): static
    {
        $app = Yii::$app;

        if (!$app instanceof static) {
            throw new InvalidCallException('This can only be called from a console application.');
        }

        return $app;
    }

    /**
     * @param array<array-key, mixed> $config
     */
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

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function coreCommands(): array
    {
        return [
            ...parent::coreCommands(),
            'asset' => AssetController::class,
            'email' => EmailController::class,
            'help' => HelpController::class,
            'maintenance' => MaintenanceController::class,
            'message' => MessageController::class,
            'migrate' => MigrateController::class,
            'params' => ParamsController::class,
            'redirect' => RedirectController::class,
            'search' => SearchController::class,
            'trail' => TrailController::class,
            'upload' => UploadController::class,
            'user' => UserController::class,
            'user-login' => UserLoginController::class,
            'user-token' => UserTokenController::class,
            'upgrade' => UpgradeController::class,
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
