<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin;

use davidhirtz\yii2\skeleton\behaviors\UserLanguageBehavior;
use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\TrailController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController;
use Yii;

class Module extends \davidhirtz\yii2\skeleton\base\Module implements ModuleInterface
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

    private ?array $_dashboardPanels = null;
    private ?array $_navBarItems = null;

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

        return parent::beforeAction($action);
    }

    protected function getCoreControllerMap(): array
    {
        return [
            'account' => [
                'class' => AccountController::class,
                'viewPath' => '@skeleton/modules/admin/views/account',
            ],
            'auth' => [
                'class' => AuthController::class,
                'viewPath' => '@skeleton/modules/admin/views/auth',
            ],
            'dashboard' => [
                'class' => DashboardController::class,
                'viewPath' => '@skeleton/modules/admin/views/dashboard',
            ],
            'redirect' => [
                'class' => RedirectController::class,
                'viewPath' => '@skeleton/modules/admin/views/redirect',
            ],
            'system' => [
                'class' => SystemController::class,
                'viewPath' => '@skeleton/modules/admin/views/system',
            ],
            'trail' => [
                'class' => TrailController::class,
                'viewPath' => '@skeleton/modules/admin/views/trail',
            ],
            'user' => [
                'class' => UserController::class,
                'viewPath' => '@skeleton/modules/admin/views/user',
            ],
            'user-login' => [
                'class' => UserLoginController::class,
                'viewPath' => '@skeleton/modules/admin/views/user-login',
            ],
        ];
    }

    public function getDashboardPanels(): array
    {
        $panels = $this->getDefaultDashboardPanels();

        foreach ($this->getSubmodules() as $module) {
            $panels = ArrayHelper::merge($panels, $module->getDashboardPanels());
        }

        if ($this->_dashboardPanels) {
            $panels = ArrayHelper::merge($panels, $this->_dashboardPanels);
        }

        return $panels;
    }

    protected function getDefaultDashboardPanels(): array
    {
        return [
            'skeleton' => [
                'name' => Yii::t('skeleton', 'Administration'),
                'items' => [
                    'user' => [
                        'label' => Yii::t('skeleton', 'Create New User'),
                        'url' => ['/admin/user/create'],
                        'icon' => 'user-plus',
                        'roles' => [User::AUTH_USER_CREATE],
                    ],
                    'account' => [
                        'label' => Yii::t('skeleton', 'Your Account'),
                        'url' => ['/admin/account/update'],
                        'icon' => 'user',
                    ],
                    'system' => [
                        'label' => Yii::t('skeleton', 'System Settings'),
                        'url' => ['/admin/system/index'],
                        'icon' => 'cog',
                        'roles' => [User::AUTH_ROLE_ADMIN],
                    ],
                    'trail' => [
                        'label' => Yii::t('skeleton', 'History'),
                        'url' => ['/admin/trail/index'],
                        'icon' => 'history',
                        'roles' => [Trail::AUTH_TRAIL_INDEX],
                    ],
                    'redirect' => [
                        'label' => Yii::t('skeleton', 'Redirects'),
                        'url' => ['/admin/redirect/index'],
                        'icon' => 'forward',
                        'roles' => [Redirect::AUTH_REDIRECT_CREATE],
                    ],
                    'homepage' => [
                        'label' => Yii::t('skeleton', 'Homepage'),
                        'url' => '/',
                        'icon' => 'globe',
                        'options' => ['target' => '_blank'],
                    ],
                ],
            ],
        ];
    }

    public function setDashboardPanels(array $panels = []): void
    {
        $this->_dashboardPanels = $panels;
    }

    public function getName(): string
    {
        return Yii::t('skeleton', 'Admin');
    }

    public function getNavBarItems(): array
    {
        $items = $this->getDefaultNavBarItems();

        foreach ($this->getSubmodules() as $module) {
            $items = ArrayHelper::merge($items, $module->getNavBarItems());
        }

        if ($this->_navBarItems) {
            $items = ArrayHelper::merge($items, $this->_navBarItems);
        }

        return $items;
    }

    protected function getDefaultNavBarItems(): array
    {
        return [
            'users' => [
                'label' => Yii::t('skeleton', 'Users'),
                'icon' => 'users',
                'url' => ['/admin/user/index'],
                'active' => ['admin/auth', 'admin/login', 'admin/user'],
                'roles' => [
                    User::AUTH_USER_ASSIGN,
                    User::AUTH_USER_UPDATE,
                ],
            ],
        ];
    }

    public function setNavBarItems(array $items = []): void
    {
        $this->_navBarItems = $items;
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
