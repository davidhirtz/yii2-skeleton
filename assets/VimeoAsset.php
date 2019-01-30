<?php
namespace davidhirtz\yii2\skeleton\assets;
use yii\web\AssetBundle;

/**
 * Class VimeoAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class VimeoAsset extends AssetBundle
{
	/**
	 * @var null
	 */
	public $sourcePath=null;

	/**
	 * @var array
	 */
	public $js=[
		[
			'//player.vimeo.com/api/player.js',
			'defer'=>true,
		],
	];
}