<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

use Yii;

/**
 * Class BaseModule.
 * @package davidhirtz\yii2\skeleton\modules\admin
 *
 * @method static Module getInstance()
 */
abstract class AdminModule extends \yii\base\Module
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
    public $controllerNamespace = 'davidhirtz\yii2\skeleton\modules\admin\controllers';

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

        parent::init();
    }
}