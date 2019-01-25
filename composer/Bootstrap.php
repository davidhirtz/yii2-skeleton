<?php
namespace davidhirtz\yii2\skeleton\composer;

use Yii;
use yii\authclient\Collection;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\i18n\PhpMessageSource;

/**
 * Class Bootstrap
 * @package davidhirtz\yii2\skeleton\bootstrap
 */
class Bootstrap implements BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		Yii::setAlias('@skeleton', dirname(__FILE__));
		Yii::setAlias('@bower', '@vendor/bower-asset');
		Yii::setAlias('@npm', '@vendor/npm-asset');

		if(is_file($params=Yii::getAlias('@app/config/params.php')))
		{
			$app->params=array_merge($app->params, require($params));
		}

//		$app->bootstrap[]='log';
//
//		$credentials=require(Yii::getAlias('@app/config/credentials.php'));
//		$app->getRequest()->cookieValidationKey=$credentials['components']['request']['cookieValidationKey'];
//
//		if(YII_DEBUG)
//		{
//			$app->bootstrap[]='debug';
//
//			$app->setModule('debug', [
//				'class'=>'yii\debug\Module',
//				'on beforeAction'=>function()
//				{
//					//Yii::$classMap['yii\bootstrap\BootstrapPluginAsset']=Yii::$app->getBasePath().'/components/helpers/BootstrapPluginAsset.php';
//				},
//			]);
//		}

//		/** @var Module $module */
//		/** @var \yii\db\ActiveRecord $modelName */
//		if($app->hasModule('user') && ($module=$app->getModule('user')) instanceof Module)
//		{
//			$this->_modelMap=array_merge($this->_modelMap, $module->modelMap);
//			foreach($this->_modelMap as $name=>$definition)
//			{
//				$class="dektrium\\user\\models\\".$name;
//				Yii::$container->set($class, $definition);
//				$modelName=is_array($definition) ? $definition['class'] : $definition;
//				$module->modelMap[$name]=$modelName;
//				if(in_array($name, ['User', 'Profile', 'Token', 'Account']))
//				{
//					Yii::$container->set($name.'Query', function() use ($modelName)
//					{
//						return $modelName::find();
//					});
//				}
//			}
//
//			Yii::$container->setSingleton(Finder::className(), [
//				'userQuery'=>Yii::$container->get('UserQuery'),
//				'profileQuery'=>Yii::$container->get('ProfileQuery'),
//				'tokenQuery'=>Yii::$container->get('TokenQuery'),
//				'accountQuery'=>Yii::$container->get('AccountQuery'),
//			]);
//
//			if($app instanceof ConsoleApplication)
//			{
//				$module->controllerNamespace='dektrium\user\commands';
//			}
//			else
//			{
//				Yii::$container->set('yii\web\User', [
//					'enableAutoLogin'=>true,
//					'loginUrl'=>['/user/security/login'],
//					'identityClass'=>$module->modelMap['User'],
//				]);
//
//				$configUrlRule=[
//					'prefix'=>$module->urlPrefix,
//					'rules'=>$module->urlRules,
//				];
//
//				if($module->urlPrefix!='user')
//				{
//					$configUrlRule['routePrefix']='user';
//				}
//
//				$configUrlRule['class']='yii\web\GroupUrlRule';
//				$rule=Yii::createObject($configUrlRule);
//
//				$app->urlManager->addRules([$rule], false);
//
//				if(!$app->has('authClientCollection'))
//				{
//					$app->set('authClientCollection', [
//						'class'=>Collection::className(),
//					]);
//				}
//			}
//
//			if(!isset($app->get('i18n')->translations['user*']))
//			{
//				$app->get('i18n')->translations['user*']=[
//					'class'=>PhpMessageSource::className(),
//					'basePath'=>__DIR__.'/messages',
//					'sourceLanguage'=>'en-US',
//				];
//			}
//
//			Yii::$container->set('dektrium\user\Mailer', $module->mailer);
//
//			$module->debug=$this->ensureCorrectDebugSetting();
//		}
	}
}