<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

use Yii;
use yii\base\Action;

/**
 * Class Module
 * @package davidhirtz\yii2\skeleton\modules\admin
 */
class Module extends \yii\base\Module
{
    /**
     * @var string the module display name, defaults to "Admin"
     */
    public $name;

    /**
     * @var string the module base route, defaults to "admin"
     */
    public $alias;

    /**
     * @var array|false|null containing the roles to access any admin module or controller, if not set the roles set
     * via {@link Module::$navbarItems} will be used. If false the module role check is disabled.
     */
    public $roles;

    /**
     * @var int|null the time in seconds after which trail records should be deleted. Leave empty to never delete trail
     * records.
     */
    public $trailLifetime;

    /**
     * @var array containing the admin menu items
     */
    public $navbarItems = [];

    /**
     * @var array containing the panel items
     */
    public $panels = [];

    /**
     * @var string
     */
    public $defaultRoute = 'dashboard';

    /**
     * @var string
     */
    public $controllerNamespace = 'app\modules\admin\controllers';

    /**
     * @var string
     */
    public $layout = '@skeleton/modules/admin/views/layouts/main';

    /**
     * @var array
     */
    protected $defaultControllerMap = [
        'account' => [
            'class' => 'davidhirtz\yii2\skeleton\controllers\AccountController',
            'viewPath' => '@skeleton/modules/admin/views/account',
        ],
        'auth' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController',
            'viewPath' => '@skeleton/modules/admin/views/auth',
        ],
        'dashboard' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController',
            'viewPath' => '@skeleton/modules/admin/views/dashboard',
        ],
        'redirect' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController',
            'viewPath' => '@skeleton/modules/admin/views/redirect',
        ],
        'system' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController',
            'viewPath' => '@skeleton/modules/admin/views/system',
        ],
        'trail' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\TrailController',
            'viewPath' => '@skeleton/modules/admin/views/trail',
        ],
        'user' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\UserController',
            'viewPath' => '@skeleton/modules/admin/views/user',
        ],
        'user-login' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController',
            'viewPath' => '@skeleton/modules/admin/views/user-login',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            $user = Yii::$app->getUser();

            if ($user->loginUrl === null) {
                $user->loginUrl = ['/admin/account/login'];
            }

            if (!$this->navbarItems) {
                $this->navbarItems = [
                    'users' => [
                        'label' => Yii::t('skeleton', 'Users'),
                        'icon' => 'users',
                        'url' => ['/admin/user/index'],
                        'active' => ['admin/auth', 'admin/login', 'admin/user'],
                        'roles' => ['authUpdate', 'userUpdate'],
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
                                'visible' => $user->can('userCreate'),
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
                                'visible' => $user->can('admin'),
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

        $this->controllerMap = array_merge($this->defaultControllerMap, $this->controllerMap);
        parent::init();
    }

    /**
     * Redirects draft URLs for the backend, but only if it's not an AJAX to prevent breaking existing
     * frontend implementations or REST APIs that use admin endpoints.
     *
     * @param Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $request = Yii::$app->getRequest();

        if ($request->getIsDraft() && !$request->getIsAjax()) {
            Yii::$app->getResponse()->redirect($request->getProductionHostInfo() . $request->getUrl())->send();
        }

        return parent::beforeAction($action);
    }
}