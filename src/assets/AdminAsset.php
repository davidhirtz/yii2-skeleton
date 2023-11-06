<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\bootstrap4\BootstrapPluginAsset;
use yii\grid\GridViewAsset;
use yii\web\AssetBundle;

class AdminAsset extends AssetBundle
{
    /**
     * @var array contains the options for the favicon link ta
     */
    public array $faviconOptions = [];

    public $css = ['css/admin.min.css'];
    public $js = ['js/admin.min.js'];
    public $sourcePath = '@skeleton/assets/admin';

    public $publishOptions = [
        'except' => [
            'scss/',
        ],
    ];

    public $depends = [
        GridViewAsset::class,
        BootstrapPluginAsset::class,
        BootboxAsset::class,
        FontAwesomeAsset::class,
    ];

    public function init(): void
    {
        if ($this->faviconOptions['href'] ?? false) {
            $this->faviconOptions['rel'] ??= 'shortcut icon';
            Yii::$app->getView()->registerLinkTag($this->faviconOptions, 'favicon');
        }

        parent::init();
    }
}
