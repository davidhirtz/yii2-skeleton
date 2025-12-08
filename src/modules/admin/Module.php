<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin;

use Hirtz\Skeleton\behaviors\UserLanguageBehavior;
use Hirtz\Skeleton\helpers\ArrayHelper;
use Hirtz\Skeleton\models\Redirect;
use Hirtz\Skeleton\models\Trail;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\config\Config;
use Hirtz\Skeleton\modules\admin\config\DashboardItemConfig;
use Hirtz\Skeleton\modules\admin\config\DashboardPanelConfig;
use Hirtz\Skeleton\modules\admin\config\MainMenuItemConfig;
use Hirtz\Skeleton\modules\admin\controllers\AccountController;
use Hirtz\Skeleton\modules\admin\controllers\AuthController;
use Hirtz\Skeleton\modules\admin\controllers\DashboardController;
use Hirtz\Skeleton\modules\admin\controllers\LogController;
use Hirtz\Skeleton\modules\admin\controllers\RedirectController;
use Hirtz\Skeleton\modules\admin\controllers\SystemController;
use Hirtz\Skeleton\modules\admin\controllers\TrailController;
use Hirtz\Skeleton\modules\admin\controllers\UserController;
use Hirtz\Skeleton\modules\admin\controllers\UserLoginController;
use Override;
use Yii;

class Module extends \Hirtz\Skeleton\base\Module implements ModuleInterface
{
    /**
     * @var string the module base route
     */
    public string $alias = 'admin';

    /**
     * @var int|null the time in seconds after which trail records should be deleted.
     * Leave empty to never delete trail records.
     */
    public ?int $trailLifetime = null;

    /**
     * @var bool whether to show the admin module in the breadcrumbs
     */
    public bool $showInBreadcrumbs = true;

    public $defaultRoute = 'dashboard/index';
    public $controllerNamespace = 'app\modules\admin\controllers';
    public $layout = '@skeleton/modules/admin/views/layouts/main';

    private array $dashboardPanels = [];
    private array $mainMenuItems = [];

    #[Override]
    public function init(): void
    {
        $controllerMap = [];

        foreach ($this->getSubmodules() as $submodule) {
            $controllerMap = ArrayHelper::merge($controllerMap, $submodule->controllerMap);
        }

        $controllerMap = ArrayHelper::merge($this->getCoreControllerMap(), $controllerMap);
        $this->controllerMap = ArrayHelper::merge($controllerMap, $this->controllerMap);

        parent::init();
    }

    #[Override]
    public function beforeAction($action): bool
    {
        $request = Yii::$app->getRequest();

        if (Yii::$app->has('user')) {
            Yii::$app->getUser()->loginUrl ??= ['/admin/account/login'];

            if (!YII_DEBUG) {
                Yii::$app->getErrorHandler()->errorView = '@skeleton/modules/admin/views/dashboard/error.php';
            }
        }

        if (!$request->getIsConsoleRequest()) {
            //  Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking frontend
            // implementations or REST APIs that use admin endpoints.
            if ($request->isDraftRequest() && !$request->getIsAjax()) {
                $url = Yii::$app->getUrlManager()->createAbsoluteUrl($request->getUrl());
                Yii::$app->getResponse()->redirect($url)->send();
            }

            if (count(Yii::$app->getI18n()->getLanguages()) > 1) {
                $action->controller->attachBehavior('UserLanguageBehavior', UserLanguageBehavior::class);
            }
        }

        $this->setViewPath('@skeleton/modules/admin/views');

        return parent::beforeAction($action);
    }

    protected function getCoreControllerMap(): array
    {
        $classMap = [
            'account' => AccountController::class,
            'auth' => AuthController::class,
            'dashboard' => DashboardController::class,
            'log' => LogController::class,
            'redirect' => RedirectController::class,
            'system' => SystemController::class,
            'trail' => TrailController::class,
            'user' => UserController::class,
            'user-login' => UserLoginController::class,
        ];

        return array_map(fn ($class) => ['class' => $class], $classMap);
    }

    /**
     * @return array<string, DashboardPanelConfig>
     */
    public function getDashboardPanels(): array
    {
        $panels = $this->getDefaultDashboardPanels();

        foreach ($this->getSubmodules() as $module) {
            foreach ($module->getDashboardPanels() as $key => $panel) {
                $panels = Config::merge($panels, $key, $panel);
            }
        }

        foreach ($this->dashboardPanels as $key => $panel) {
            $panels = Config::merge($panels, $key, $panel);
        }

        return array_filter($panels);
    }

    /**
     * @return array<string, DashboardPanelConfig>
     */
    protected function getDefaultDashboardPanels(): array
    {
        return [
            'skeleton' => new DashboardPanelConfig(
                name: Yii::t('skeleton', 'Administration'),
                items: [
                    'user' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'Create New User'),
                        url: ['/admin/user/create'],
                        icon: 'user-plus',
                        roles: [User::AUTH_USER_CREATE],
                    ),
                    'account' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'Your Account'),
                        url: ['/admin/account/update'],
                        icon: 'user',
                    ),
                    'system' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'System Settings'),
                        url: ['/admin/system/index'],
                        icon: 'cog',
                        roles: [User::AUTH_ROLE_ADMIN],
                    ),
                    'trail' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'History'),
                        url: ['/admin/trail/index'],
                        icon: 'history',
                        roles: [Trail::AUTH_TRAIL_INDEX],
                    ),
                    'redirect' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'Redirects'),
                        url: ['/admin/redirect/index'],
                        icon: 'forward',
                        roles: [Redirect::AUTH_REDIRECT_CREATE],
                    ),
                    'homepage' => new DashboardItemConfig(
                        label: Yii::t('skeleton', 'Homepage'),
                        url: '/',
                        icon: 'globe',
                        attributes: ['target' => '_blank'],
                    ),
                ]
            ),
        ];
    }

    /**
     * @param array<DashboardItemConfig|null> $panels
     */
    public function setDashboardPanels(array $panels = []): void
    {
        $this->dashboardPanels = $panels;
    }

    public function getName(): string
    {
        return Yii::t('skeleton', 'Admin');
    }

    /**
     * @return array<string, MainMenuItemConfig>
     */
    public function getMainMenuItems(): array
    {
        $items = $this->getDefaultMainMenuItems();

        foreach ($this->getSubmodules() as $module) {
            foreach ($module->getMainMenuItems() as $key => $item) {
                $items = Config::merge($items, $key, $item);
            }
        }

        foreach ($this->mainMenuItems as $key => $item) {
            $items = Config::merge($items, $key, $item);
        }

        return array_filter($items);
    }

    /**
     * @return array<string, MainMenuItemConfig>
     */
    protected function getDefaultMainMenuItems(): array
    {
        return [
            'users' => new MainMenuItemConfig(
                label: Yii::t('skeleton', 'Users'),
                url: ['/admin/user/index'],
                icon: 'users',
                roles: [
                    User::AUTH_USER_ASSIGN,
                    User::AUTH_USER_UPDATE,
                ],
                routes: [
                    'admin/auth',
                    'admin/login',
                    'admin/user',
                    'admin/trail/index' => ['user'],
                ],
                order: 100,
            ),
        ];
    }

    public function setMainMenuItems(array $items): void
    {
        $this->mainMenuItems = $items;
    }

    /**
     * @return (\yii\base\Module&ModuleInterface)[]
     */
    public function getSubmodules(): array
    {
        $modules = [];

        foreach (array_keys($this->getModules()) as $moduleName) {
            $module = $this->getModule($moduleName);

            if ($module instanceof ModuleInterface) {
                $modules[] = $module;
            }
        }

        return $modules;
    }
}
