<?php

namespace davidhirtz\yii2\skeleton\assets;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\web\AssetBundle;

/**
 * Class AdminAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class AdminAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/admin';

    /**
     * @var array
     */
    public $css = [
        'css/admin.min.css',
    ];

    /**
     * @var array
     */
    public $js = [
        'js/jquery-ui.min.js',
        'js/admin.min.js',
    ];

    /**
     * @var array
     */
    public $depends = [
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
        Yii::$app->getAssetManager()->bundles['yii\bootstrap4\BootstrapAsset'] = [
			'sourcePath'=>null,
			'css'=>[],
        ];

        if (YII_DEBUG) {
            ArrayHelper::replaceValue($this->css, 'css/admin.min.css', 'css/admin.css');
            ArrayHelper::replaceValue($this->js, 'js/jquery-ui.min.js', 'js/jquery-ui.js');
            ArrayHelper::replaceValue($this->js, 'js/admin.min.js', 'js/admin.js');
        }

        parent::init();
    }
}
