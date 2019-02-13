<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

use Yii;

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
     * @var string the module base route, defaults to the module id
     */
    public $alias;

    /**
     * @var array containing the admin menu items
     */
    public $navItems = [];

    /**
     * @var string
     */
    //public $controllerNamespace = 'davidhirtz\yii2\skeleton\modules\admin\controllers';

    /**
     * @var string
     */
    public $defaultRoute = 'dashboard';

    /**
     * @var string
     */
    public $layout = '@skeleton/modules/admin/views/layouts/main';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
            Yii::$app->getUser()->loginUrl = ['/admin/account/login'];
            //Yii::$app->getErrorHandler()->errorAction='/admin/dashboard/error';

            if ($this->name !== false) {
                Yii::$app->getView()->setBreadcrumb($this->name ?: Yii::t('skeleton', 'Admin'), ['/admin/dashboard/index']);
            }
        }

        $this->registerCoreControllers();

        parent::init();
    }

    /**
     * Registers the core skeleton admin controllers.
     */
    protected function registerCoreControllers()
    {
        $controllerMap = [
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

        $this->controllerMap = array_merge($controllerMap, $this->controllerMap);
    }
}
