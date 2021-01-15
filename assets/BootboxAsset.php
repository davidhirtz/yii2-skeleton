<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Class BootboxAsset.
 * @package davidhirtz\yii2\skeleton\assets
 */
class BootboxAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@npm/bootbox/dist';

    /**
     * @var array
     */
    public $js = [
        'bootbox.min.js',
    ];

    /**
     * Loads locale based on app language.
     */
    public function init()
    {
        if (Yii::$app->language !== Yii::$app->sourceLanguage) {
            $this->js = ['bootbox.all.min.js'];
            Yii::$app->getView()->registerJs('bootbox.setLocale("' . Yii::$app->language . '");');
        }

        parent::init();
    }
}