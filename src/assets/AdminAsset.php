<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\bootstrap4\BootstrapPluginAsset;
use yii\grid\GridViewAsset;
use yii\web\AssetBundle;

/**
 * Includes the default CSS and JS files for the admin area.
 */
class AdminAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $css = ['css/admin.min.css'];

    /**
     * @var array
     */
    public $depends = [
        GridViewAsset::class,
        BootstrapPluginAsset::class,
        BootboxAsset::class,
        FontAwesomeAsset::class,
    ];

    /**
     * @var array contains the options for the favicon link ta
     */
    public array $faviconOptions = [];

    /**
     * @var array
     */
    public $js = ['js/admin.min.js'];

    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/admin';

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
        if ($this->faviconOptions) {
            $this->faviconOptions['rel'] ??= 'shortcut icon';
            Yii::$app->getView()->registerLinkTag($this->faviconOptions, 'favicon');
        }

        parent::init();
    }
}
