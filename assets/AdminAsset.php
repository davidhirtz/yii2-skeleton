<?php

namespace davidhirtz\yii2\skeleton\assets;

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
    public $css = [YII_DEBUG ? 'css/admin.css' : 'css/admin.min.css'];

    /**
     * @var array
     */
    public $js = [YII_DEBUG ? 'js/admin.js' : 'js/admin.min.js'];

    /**
     * @var array
     */
    public array $faviconOptions = [];

    /**
     * @var array
     */
    public $depends = [
        'yii\grid\GridViewAsset',
        'yii\bootstrap4\BootstrapPluginAsset',
        'davidhirtz\yii2\skeleton\assets\BootboxAsset',
        'davidhirtz\yii2\skeleton\assets\FontAwesomeAsset',
    ];

    /**
     * @var array
     */
    public $publishOptions = [
        'except' => [
            'scss/',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function init()
    {
        Yii::$app->getAssetManager()->bundles['yii\bootstrap4\BootstrapAsset'] = [
            'sourcePath' => null,
            'css' => [],
        ];

        if ($this->faviconOptions) {
            $this->faviconOptions['rel'] ??= 'shortcut icon';
            Yii::$app->getView()->registerLinkTag($this->faviconOptions, 'favicon');
        }

        parent::init();
    }
}
