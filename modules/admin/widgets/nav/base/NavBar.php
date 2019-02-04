<?php
namespace davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base;

use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\NavBarInterface;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Nav;
use Yii;
use yii\helpers\Url;

/**
 * Class NavBar.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\nav\base
 */
class NavBar extends \yii\bootstrap4\NavBar implements NavBarInterface
{
	/**
	 * @var array
	 */
	public $options=[
		'class'=>'navbar navbar-expand-md',
	];

	/**
	 * @var array
	 */
	public static $excludedModules=['admin', 'debug', 'gii'];

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		echo Nav::widget([
			'options'=>['class'=>'navbar-nav'],
			'items'=>static::getItems(),
		]);

		if($items=static::getAccountItems())
		{
			echo Nav::widget([
				'options'=>['class'=>'navbar-nav'],
				'items'=>$items,
			]);
		}

		parent::run();
	}

	/**
	 * @return array
	 */
	public static function getItems()
	{
		return array_merge(static::getHomeItems(), static::getModuleItems(), static::getUserItems());
	}

	/**
	 * @return array
	 */
	protected static function getModuleItems()
	{
		$modules=Yii::$app->getModules();
		$items=[];

		foreach($modules as $name=>$module)
		{
			if(!in_array($name, static::$excludedModules))
			{
				/**
				 * @var NavBarInterface $className
				 */
				if(class_exists($className="app\\modules\\{$name}\\modules\\admin\\components\\widgets\\nav\\NavBar"))
				{
					$items=array_merge($items, $className::getItems());
				}
			}
		}

		return $items;
	}

	/**
	 * @return array
	 */
	protected static function getAccountItems()
	{
		$user=Yii::$app->getUser();

		if($user->getIsGuest())
		{
			return [
				[
					'label'=>Yii::t('app', 'Login'),
					'icon'=>'sign-in-alt',
					'url'=>$user->loginUrl,
				],
				[
					'label'=>Yii::t('app', 'Sign up'),
					'icon'=>'plus-circle',
					'url'=>['/admin/account/create'],
					'visible'=>Yii::$app->getUser()->isSignupEnabled(),
				],
			];
		}

		$i18n=Yii::$app->getI18n();
		$items=[];

		foreach($i18n->getLanguages() as $language)
		{
			$items[]=[
				'label'=>'<i class="i18n-icon '.$language.'"></i><span class="i18n-label">'.$i18n->getLabel($language).'</span>',
				'url'=>Url::current(['language'=>$language]),
				'encode'=>false,
			];
		}

		return [
			[
				'label'=>'<i class="i18n-icon '.Yii::$app->language.'"></i>',
				'icon'=>false,
				'url'=>'#', // Bootstrap 4.2 fix
				'visible'=>count($items)>1,
				'encode'=>false,
				'items'=>$items,
				'options'=>[
					'class'=>'i18n-dropdown',
				],
			],
			[
				'label'=>$user->getIdentity()->getUsername(),
				'icon'=>'user',
				'url'=>['/admin/account/update'],
				'labelOptions'=>[
					'class'=>'hidden-xs',
				],
			],
			[
				'label'=>Yii::t('app', 'Logout'),
				'icon'=>'sign-out',
				'url'=>['/admin/account/logout'],
				'linkOptions'=>[
					'data-method'=>'post',
				],
				'labelOptions'=>[
					'class'=>'hidden-xs',
				],
			],
		];
	}

	/**
	 * @return array
	 */
	protected static function getHomeItems()
	{
		if(!Yii::$app->getUser()->getIsGuest())
		{
			return [
				[
					'label'=>Yii::t('app', 'Home'),
					'icon'=>'home',
					'url'=>['/admin/site/index'],
					'labelOptions'=>[
						'class'=>'hidden-xs',
					],
				],
			];
		}

		return [];
	}

	/**
	 * @return array
	 */
	protected static function getUserItems()
	{
		if(Yii::$app->getUser()->can('userUpdate'))
		{
			return [
				[
					'label'=>Yii::t('app', 'Users'),
					'icon'=>'users',
					'url'=>['/admin/user/index'],
					'active'=>['admin/auth', 'admin/login', 'admin/user'],
					'labelOptions'=>[
						'class'=>'hidden-xs',
					],
				]
			];
		}

		return [];
	}
}