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
use Hirtz\Skeleton\Console\Controllers\RegistryController;
use Hirtz\Skeleton\Console\Controllers\SearchController;
use Hirtz\Skeleton\Console\Controllers\TrailController;
use Hirtz\Skeleton\Console\Controllers\UpgradeController;
use Hirtz\Skeleton\Console\Controllers\UserController;
use Hirtz\Skeleton\Console\Controllers\ConsentController;
use Hirtz\Skeleton\Console\Controllers\UserLoginController;
use Hirtz\Skeleton\Console\Controllers\UploadController;
use Hirtz\Skeleton\Console\Controllers\UserTokenController;
use Override;
use ReflectionMethod;
use Yii;
use yii\base\Action;
use yii\base\InlineAction;
use yii\base\InvalidCallException;
use yii\console\Exception;

class Application extends \yii\console\Application
{
    use ApplicationTrait;

    public $controllerNamespace = 'App\\Commands';

    /**
     * @var list<mixed> the positional arguments of the action about to run, checked and cleared in {@see beforeAction()}
     */
    private array $arguments = [];

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
        $this->setDefaultEmail();

        $this->setControllerPath(Yii::getAlias('@app/Commands'));

        parent::bootstrap();
    }

    /**
     * Without a request, the host comes from the pinned `params.hostInfo` or a configured `urlManager.hostInfo`; the
     * URL manager is not built for it, as a bundle's may read the database a fresh installation does not have yet.
     */
    protected function setDefaultEmail(): void
    {
        if (isset($this->params['email'])) {
            return;
        }

        $urlManager = $this->getComponents()['urlManager'] ?? null;
        $hostInfo = $this->params['hostInfo'] ?? (is_array($urlManager) ? $urlManager['hostInfo'] ?? null : null);
        $host = is_string($hostInfo) ? parse_url($hostInfo, PHP_URL_HOST) : null;

        if (is_string($host) && $host !== '') {
            $this->params['email'] = "hostmaster@$host";
        }
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
            'consent' => ConsentController::class,
            'email' => EmailController::class,
            'help' => HelpController::class,
            'maintenance' => MaintenanceController::class,
            'message' => MessageController::class,
            'migrate' => MigrateController::class,
            'params' => ParamsController::class,
            'redirect' => RedirectController::class,
            'registry' => RegistryController::class,
            'search' => SearchController::class,
            'trail' => TrailController::class,
            'upload' => UploadController::class,
            'user' => UserController::class,
            'user-login' => UserLoginController::class,
            'user-token' => UserTokenController::class,
            'upgrade' => UpgradeController::class,
        ];
    }

    /**
     * @param string $route
     * @param array<array-key, mixed> $params
     */
    #[Override]
    public function runAction($route, $params = [])
    {
        $this->arguments = array_values(array_filter($params, is_int(...), ARRAY_FILTER_USE_KEY));
        return parent::runAction($route, $params);
    }

    /**
     * Yii hands an argument the action does not take to the method anyway, where PHP drops it, so a mistyped option
     * (`transformation/delete name jpg` for `--extension=jpg`) runs the command without it.
     */
    #[Override]
    public function beforeAction($action): bool
    {
        $this->ensureNoUnexpectedArguments($action);
        return parent::beforeAction($action);
    }

    protected function ensureNoUnexpectedArguments(Action $action): void
    {
        $method = $action instanceof InlineAction
            ? new ReflectionMethod($action->controller, $action->actionMethod)
            : new ReflectionMethod($action, 'run');

        $unexpected = $method->isVariadic() ? [] : array_slice($this->arguments, $method->getNumberOfParameters());
        $this->arguments = [];

        if ($unexpected) {
            throw new Exception('Unexpected arguments: ' . implode(', ', array_map(strval(...), $unexpected)));
        }
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
