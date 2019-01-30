<?php
namespace davidhirtz\yii2\skeleton\assets;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use yii\web\AssetBundle;

/**
 * Class AppAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class AppAsset extends AssetBundle
{
	/**
	 * @var string
	 */
	public $sourcePath='@skeleton/assets/app';

	/**
	 * @var array
	 */
	public $css=[
		'css/app.min.css',
	];

	/**
	 * @var array
	 */
	public $js=[
		'js/jquery-ui.min.js',
		'js/app.min.js',
	];

	/**
	 * @var array
	 */
	public $depends=[
		'yii\grid\GridViewAsset',
		'rmrevin\yii\fontawesome\CdnFreeAssetBundle',
		'yii\bootstrap4\BootstrapPluginAsset',
		'davidhirtz\yii2\skeleton\assets\BootboxAsset',
	];

	/**
	 * Debug.
	 */
	public function init()
	{
		if(YII_DEBUG)
		{
			ArrayHelper::replaceValue($this->css, 'css/app.min.css', 'css/app.css');
			ArrayHelper::replaceValue($this->js, 'js/jquery-ui.min.js', 'js/jquery-ui.js');
			ArrayHelper::replaceValue($this->js, 'js/app.min.js', 'js/app.js');
		}

		parent::init();
	}
}
