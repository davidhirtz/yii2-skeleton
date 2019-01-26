<?php
namespace davidhirtz\yii2\skeleton\modules\admin;

use Yii;

/**
 * Class BaseModule.
 * @package davidhirtz\yii2\skeleton\modules\admin
 */
class BaseModule extends \yii\base\Module
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $controllerNamespace='davidhirtz\yii2\skeleton\modules\admin\controllers';

	/**
	 * @var string
	 */
	public $layout='@app/modules/admin/views/layouts/main';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if(!Yii::$app->getRequest()->getIsConsoleRequest())
		{
			Yii::$app->getUser()->loginUrl=['/admin/account/login'];
			Yii::$app->getErrorHandler()->errorAction='/admin/site/error';

			if($this->name!==false)
			{
				Yii::$app->getView()->setBreadcrumb($this->name ?: Yii::t('app', 'Admin'), ['/admin/site/index']);
			}
		}

		parent::init();
	}
}