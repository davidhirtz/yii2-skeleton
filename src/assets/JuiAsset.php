<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Class JuiAsset
 * @package davidhirtz\yii2\skeleton\assets
 */
class JuiAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@skeleton/assets/jui/';

    /**
     * @var array
     */
    public $js = [
        YII_DEBUG ? 'jquery-ui.js' : 'jquery-ui.min.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /**
     * Adds i18n support.
     */
    public function init()
    {
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            $this->js[] = 'i18n/datepicker-' . Yii::$app->language . '.js';
        }

        parent::init();
    }
}
