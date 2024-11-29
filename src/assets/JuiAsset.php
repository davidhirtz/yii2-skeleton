<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

class JuiAsset extends AssetBundle
{
    public $sourcePath = '@skeleton/assets/jui/';

    public $js = [
        YII_DEBUG ? 'jquery-ui.js' : 'jquery-ui.min.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];

    public function init(): void
    {
        if (Yii::$app->language != Yii::$app->sourceLanguage) {
            $this->js[] = 'i18n/datepicker-' . Yii::$app->language . '.js';
        }

        parent::init();
    }
}
