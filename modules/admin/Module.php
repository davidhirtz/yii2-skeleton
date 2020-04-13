<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

use Yii;
use yii\helpers\Url;

/**
 * Class Module.
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
     * @var array containing the roles to access any admin module or controller
     */
    public $roles = ['admin'];

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
        'system' => [
            'class' => 'davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController',
            'viewPath' => '@skeleton/modules/admin/views/system',
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
     * @inheritdoc
     */
    public function init()
    {
        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            $user = Yii::$app->getUser();

            if ($user->loginUrl === null) {
                $user->loginUrl = ['/admin/account/login'];
            }

            if (!$this->panels) {
                $this->panels = [
                    [
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

        // Load all admin modules.
        foreach (array_keys($this->getModules()) as $module) {
            $this->getModule($module);
        }

        $this->controllerMap = array_merge($this->defaultControllerMap, $this->controllerMap);
        parent::init();
    }

    /**
     * Redirects draft URLs for the backend.
     *
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $request = Yii::$app->getRequest();

        if ($request->getIsDraft()) {
            $request->setHostInfo($request->getProductionHostInfo());
            Yii::$app->getResponse()->redirect(Url::current([], true))->send();
        }

        return parent::beforeAction($action);
    }
}
