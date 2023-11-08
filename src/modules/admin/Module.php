<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\TrailController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController;
use Yii;
use yii\base\Action;

class Module extends \yii\base\Module
{
    public const EVENT_AFTER_INIT = 'afterInit';

    /**
     * @var string|null the module display name, defaults to "Admin"
     */
    public ?string $name = null;

    /**
     * @var string|null the module base route, defaults to "admin"
     */
    public ?string $alias = null;

    /**
     * @var array|false|null containing the roles to access any admin module or controller, if not set roles set
     * via {@see Module::$navbarItems} will be used. If false, the module role check is disabled.
     */
    public array|false|null $roles = null;

    /**
     * @var int|null the time in seconds after which trail records should be deleted. Leave empty to never delete trail
     * records.
     */
    public ?int $trailLifetime = null;

    /**
     * @var array containing the admin menu items
     */
    public array $navbarItems = [];

    /**
     * @var array containing the panel items
     */
    public array $panels = [];

    public $defaultRoute = 'dashboard';
    public $controllerNamespace = 'app\modules\admin\controllers';
    public $layout = '@skeleton/modules/admin/views/layouts/main';

    /**
     * @var array the default controller map
     */
    protected array $defaultControllerMap = [
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

    public function init(): void
    {
        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            $user = Yii::$app->getUser();

            $user->loginUrl ??= ['/admin/account/login'];
            Yii::$app->getErrorHandler()->errorView = '@skeleton/modules/admin/views/dashboard/error.php';

            if (!$this->navbarItems) {
                $this->navbarItems = [
                    'users' => [
                        'label' => Yii::t('skeleton', 'Users'),
                        'icon' => 'users',
                        'url' => ['/admin/user/index'],
                        'active' => ['admin/auth', 'admin/login', 'admin/user'],
                        'roles' => [User::AUTH_USER_ASSIGN, User::AUTH_USER_UPDATE],
                    ],
                ];
            }

            if (!$this->panels) {
                $this->panels = [
                    'skeleton' => [
                        'name' => $this->name ?: Yii::t('skeleton', 'Administration'),
                        'items' => [
                            [
                                'label' => Yii::t('skeleton', 'Create New User'),
                                'url' => ['/admin/user/create'],
                                'icon' => 'user-plus',
                                'visible' => $user->can(User::AUTH_USER_CREATE),
                            ],
                            [
                                'label' => Yii::t('skeleton', 'Your Account'),
                                'url' => ['/admin/account/update'],
                                'icon' => 'user',
                            ],
                            [
                                'label' => Yii::t('skeleton', 'System Settings'),
                                'url' => ['/admin/system/index'],
                                'icon' => 'cog',
                                'visible' => $user->can(User::AUTH_ROLE_ADMIN),
                            ],
                            [
                                'label' => Yii::t('skeleton', 'History'),
                                'url' => ['/admin/trail/index'],
                                'icon' => 'history',
                                'visible' => $user->can('trailIndex'),
                            ],
                            [
                                'label' => Yii::t('skeleton', 'Redirects'),
                                'url' => ['/admin/redirect/index'],
                                'icon' => 'forward',
                                'visible' => $user->can('redirectCreate'),
                            ],
                            [
                                'label' => Yii::t('skeleton', 'Homepage'),
                                'url' => '/',
                                'icon' => 'globe',
                                'options' => ['target' => '_blank'],
                            ],
                        ],
                    ],
                ];
            }

            $this->controllerMap = [...$this->defaultControllerMap, ...$this->controllerMap];
        }

        foreach (array_keys($this->getModules()) as $module) {
            $this->getModule($module);
        }

        if ($this->roles === null) {
            $this->roles = [];

            foreach ($this->navbarItems as $item) {
                $this->roles = array_filter(array_merge($this->roles, $item['roles'] ?? []));
            }
        }

        $this->trigger(static::EVENT_AFTER_INIT);
        parent::init();
    }

    /**
     * Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking existing
     * frontend implementations or REST APIs that use admin endpoints.
     *
     * @param Action $action
     */
    public function beforeAction($action): bool
    {
        $request = Yii::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsDraft() && !$request->getIsAjax()) {
            Yii::$app->getResponse()->redirect($request->getProductionHostInfo() . $request->getUrl())->send();
        }

        return parent::beforeAction($action);
    }
}
