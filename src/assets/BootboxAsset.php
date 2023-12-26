<?php

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Includes the Bootbox javascript library.
 */
class BootboxAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $js = ['bootbox.min.js'];

    /**
     * @var string
     */
    public $sourcePath = '@npm/bootbox/dist';

    /**
     * Loads locale based on app language.
     */
    public function init(): void
    {
        if (Yii::$app->language !== Yii::$app->sourceLanguage) {
            $this->js = ['bootbox.all.min.js'];
            Yii::$app->getView()->registerJs('bootbox.setLocale("' . Yii::$app->language . '");');
        }

        parent::init();
    }
}
