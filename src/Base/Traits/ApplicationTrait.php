<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Base\Traits;

use Hirtz\Skeleton\Assets\EmptyAssetBundle;
use Hirtz\Skeleton\Base\RootPackage;
use Hirtz\Skeleton\Controllers\HealthController;
use Hirtz\Skeleton\Controllers\SitemapController;
use Hirtz\Skeleton\Db\Connection;
use Hirtz\Skeleton\I18n\I18N;
use Hirtz\Skeleton\Log\FileTarget;
use Hirtz\Skeleton\Log\SentryTarget;
use Hirtz\Skeleton\Caching\CacheComponents;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\DatabaseComponents;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Rbac\DbManager;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Upload\Upload;
use Hirtz\Skeleton\Web\DbSession;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Skeleton\Web\View;
use Yii;
use yii\caching\FileCache;
use yii\helpers\ArrayHelper;
use yii\i18n\PhpMessageSource;
use yii\symfonymailer\Mailer;
use yii\validators\TrimValidator;
use yii\web\JqueryAsset;

/**
 * @property Search $search
 * @property Sitemap $sitemap
 * @property Upload $upload
 * @property UrlManager $urlManager
 *
 * @method DbManager getAuthManager()
 * @method Connection getDb()
 * @method I18N getI18n()
 * @method Mailer getMailer()
 * @method UrlManager getUrlManager()
 * @method View getView()
 */
trait ApplicationTrait
{
    /**
     * Collected under both SAPIs, so `Db\MigrationHistory` can report pending migrations from a web request;
     * `Console\Controllers\MigrateController` takes its namespaces from here.
     *
     * @var list<string>
     */
    private array $migrationNamespaces = [];

    /**
     * @param array<array-key, mixed> $config
     */
    protected function preInitInternal(&$config): void
    {
        Yii::$classMap = [...Yii::$classMap, ...ArrayHelper::remove($config, 'classMap', [])];

        ActiveQuery::resetStatus();
        CacheComponents::reset();
        DatabaseComponents::reset();
        DefinitionRegistry::reset();
        TrailModelCollection::reset();

        $this->setMigrationNamespace('app\Migrations');
        $this->setMigrationNamespace('Hirtz\Skeleton\Migrations');

        $core = [
            'id' => 'skeleton',
            'aliases' => [
                '@root' => $config['basePath'],
                '@skeleton' => dirname(__FILE__, 3),
                '@app' => '@root/app',
                '@messages' => '@root/messages',
                '@resources' => '@root/resources',
                '@views' => '@resources/views',
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'bootstrap' => [
                'log',
            ],
            'components' => [
                'assetManager' => [
                    'bundles' => [
                        JqueryAsset::class => [
                            'class' => EmptyAssetBundle::class,
                        ],
                    ],
                ],
                'authManager' => [
                    'class' => DbManager::class,
                ],
                'cache' => [
                    'class' => FileCache::class,
                ],
                'db' => [
                    'class' => Connection::class,
                    'enableSchemaCache' => true,
                    'charset' => 'utf8mb4',
                ],
                'i18n' => [
                    'class' => I18N::class,
                    'translations' => [
                        'app' => [
                            'class' => PhpMessageSource::class,
                            'sourceLanguage' => 'en-US',
                            'basePath' => '@messages',
                        ],
                    ],
                ],
                'log' => [
                    'traceLevel' => YII_DEBUG ? 3 : 0,
                    'targets' => [
                        'file' => [
                            'class' => FileTarget::class,
                            'levels' => ['error', 'warning'],
                            'fileMode' => 0770, // Make sure both web and console user can write to file
                            // The session id and the identity cookie are credentials, and a reset or
                            // confirmation link carries its token in the query string — which `maskVars` cannot
                            // reach inside `_SERVER.REQUEST_URI`, hence the target's own `maskQueryParams`.
                            'logVars' => [
                                '_GET',
                                '_POST',
                                '_FILES',
                                '_SERVER',
                            ],
                            'maskVars' => [
                                '_GET.code',
                                '_SERVER.HTTP_AUTHORIZATION',
                                '_SERVER.HTTP_COOKIE',
                                '_SERVER.PHP_AUTH_USER',
                                '_SERVER.PHP_AUTH_PW',
                                '_POST.value',
                                '_POST.AccountCredentialsForm.newPassword',
                                '_POST.AccountCredentialsForm.oldPassword',
                                '_POST.AccountCredentialsForm.repeatPassword',
                                '_POST.GoogleAuthenticator.code',
                                '_POST.Login.code',
                                '_POST.Login.password',
                                '_POST.PasswordResetForm.code',
                                '_POST.PasswordResetForm.newPassword',
                                '_POST.PasswordResetForm.repeatPassword',
                                '_POST.SignupForm.password',
                                '_POST.User.newPassword',
                                '_POST.User.oldPassword',
                                '_POST.User.password',
                                '_POST.User.repeatPassword',
                                '_POST.UserForm.newPassword',
                                '_POST.UserForm.repeatPassword',
                            ],
                            'except' => [
                                'yii\web\HttpException:*',
                            ],
                        ],
                    ],
                ],
                'mailer' => [
                    'class' => Mailer::class,
                    'htmlLayout' => '@skeleton/../resources/mail/layouts/html',
                    'useFileTransport' => YII_DEBUG,
                ],
                'search' => [
                    'class' => Search::class,
                    'models' => [
                        User::class,
                    ],
                ],
                'session' => [
                    'class' => DbSession::class,
                    'name' => '_session',
                ],
                'sitemap' => [
                    'class' => Sitemap::class,
                ],
                'upload' => [
                    'class' => Upload::class,
                ],
                'urlManager' => [
                    'class' => UrlManager::class,
                ],
                'view' => [
                    'class' => View::class,
                ],
            ],
            'container' => [
                'definitions' => [
                    TrimValidator::class => \Hirtz\Skeleton\Validators\TrimValidator::class,
                ],
            ],
            'controllerMap' => [
                'health' => HealthController::class,
                'sitemap' => SitemapController::class,
            ],
            'modules' => [
                'admin' => [
                    'class' => Module::class,
                    'viewPath' => '@app/modules/admin/views',
                ],
            ],
            'viewPath' => '@views',
        ];

        $config = ArrayHelper::merge($core, $config);

        $this->addRootPackageBootstrap($config);

        $path = "{$config['basePath']}/config/";

        if (!YII_ENV_TEST) {
            $file = "{$path}params.php";

            if (is_file($file)) {
                $config['params'] = [...$config['params'] ?? [], ...require($file)];
            }
        }

        $file = "{$path}db.php";

        if (is_file($file)) {
            $config['components']['db'] = [...require($file), ...$config['components']['db']];
        }

        // Make sure the cache prefix via params is applied before application bootstrap, as a DB session might get
        // started which could trigger the database schema cache.
        $cacheKeyPrefix = $config['params']['cacheKeyPrefix'] ?? null;

        if ($cacheKeyPrefix) {
            $config['components']['cache']['keyPrefix'] = $cacheKeyPrefix;
        }

        $this->setDefaultMailerDsn($config);
        $this->setSentryLogTarget($config);
    }

    /**
     * Beside the file target rather than instead of it, and only where the parameter is set: an installation with
     * no `sentryDsn` never builds a Sentry client.
     *
     * @param array<array-key, mixed> $config
     */
    protected function setSentryLogTarget(&$config): void
    {
        $dsn = $config['params']['sentryDsn'] ?? null;

        if (!is_string($dsn) || !$dsn) {
            return;
        }

        $config['components']['log']['targets']['sentry'] = ArrayHelper::merge([
            'class' => SentryTarget::class,
            'dsn' => $dsn,
            'levels' => ['error', 'warning'],
            // A 404 is not an error worth a report, while a 5xx is the one that matters most.
            'except' => [
                'yii\\web\\HttpException:4*',
            ],
        ], $config['components']['log']['targets']['sentry'] ?? []);
    }

    /**
     * Composer never lists the root package in `vendor/yiisoft/extensions.php`, so a bundle installed on its own
     * — which is how a project's Composer sees every bundle but the one it is testing — has to be wired up here.
     *
     * @param array<string, mixed> $config
     */
    protected function addRootPackageBootstrap(array &$config): void
    {
        $package = new RootPackage($config['basePath']);

        // the aliases last, so a configured one still wins
        $config['aliases'] = [...$package->getAliases(), ...$config['aliases']];

        if (($bootstrap = $package->getBootstrap()) !== null) {
            $config['bootstrap'][] = $bootstrap;
        }
    }

    /**
     * @param array<array-key, mixed> $config
     */
    protected function setDefaultMailerDsn(&$config): void
    {
        if (!empty($config['components']['mailer']['useFileTransport'])) {
            return;
        }

        if (isset($config['components']['mailer']['transport']) && !is_array($config['components']['mailer']['transport'])) {
            return;
        }

        $config['components']['mailer']['transport']['dsn'] = $config['params']['mailerDsn']
            ?? $config['components']['mailer']['transport']['dsn']
            ?? 'sendmail://default';
    }

    public function getAdminAlias(): string
    {
        return trim((string)($this->params['adminAlias'] ?? ''), '/') ?: 'admin';
    }

    protected function setDefaultUrlManagerRules(): void
    {
        $alias = $this->getAdminAlias();

        $this->addUrlManagerRules([
            'application-health' => 'health/index',
            'sitemap.xml' => 'sitemap/index',
            "$alias/<module>/<controller>/<view>" => 'admin/<module>/<controller>/<view>',
            "$alias/<controller>/<view>" => 'admin/<controller>/<view>',
            "$alias/<controller>" => 'admin/<controller>',
            "$alias/?" => 'admin/',
        ]);
    }

    /**
     * @param array<array-key, mixed> $rules
     */
    public function addUrlManagerRules(array $rules, bool $prepend = false): void
    {
        $component = $this->getComponents()['urlManager'];

        $component['rules'] ??= [];
        $component['rules'] = $prepend ? [...$rules, ...$component['rules']] : [...$component['rules'], ...$rules];

        $this->set('urlManager', $component);
    }

    /**
     * Extends given application component.
     *
     * @param array<string, mixed> $definition
     */
    public function extendComponent(string $id, array $definition): void
    {
        $this->set($id, ArrayHelper::merge($definition, $this->getComponents()[$id] ?? []));
    }

    /**
     * @param array<string, array<string, mixed>> $components
     */
    public function extendComponents(array $components): void
    {
        foreach ($components as $id => $definition) {
            $this->extendComponent($id, $definition);
        }
    }

    /**
     * @param array<string, mixed> $module
     */
    public function extendModule(string $id, array $module): void
    {
        if ($module) {
            $this->setModule($id, ArrayHelper::merge($module, $this->getModules()[$id] ?? []));
        }
    }

    /**
     * @param array<string, array<string, mixed>> $modules
     */
    public function extendModules(array $modules): void
    {
        foreach ($modules as $id => $config) {
            $this->extendModule($id, $config);
        }
    }

    public function setMigrationNamespace(string $namespace): void
    {
        if (!in_array($namespace, $this->migrationNamespaces, true)) {
            $this->migrationNamespaces[] = $namespace;
        }
    }

    /**
     * @return list<string>
     */
    public function getMigrationNamespaces(): array
    {
        return $this->migrationNamespaces;
    }
}
