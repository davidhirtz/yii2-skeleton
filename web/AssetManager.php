<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;

/**
 * Class AssetManager.
 * @package davidhirtz\yii2\skeleton\web
 */
class AssetManager extends \yii\web\AssetManager
{
    /**
     * @var bool
     */
    public $appendTimestamp = true;

    /**
     * @var bool
     */
    public $linkAssets = true;

    /**
     * @var bool
     */
    public $combine = false;

    /**
     * @var array
     */
    public $combineOptions = [];

    /**
     * @var string
     */
    public $combinedBundlesAlias = '@app/config/bundles.php';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->combine) {
            if (!Yii::$app->getRequest()->getIsConsoleRequest()) {
                $this->bundles = array_merge($this->bundles, require(Yii::getAlias($this->combinedBundlesAlias)));
            }
        }

        if (!empty($this->combineOptions['targets'])) {
            foreach ($this->combineOptions['targets'] as $name => &$options) {
                if (!isset($options['class'])) {
                    $options['class'] = 'yii\web\AssetBundle';
                }

                if (!isset($options['basePath'])) {
                    $options['basePath'] = '@webroot';
                }

                if (!isset($options['baseUrl'])) {
                    $options['baseUrl'] = '@web';
                }
            }
        }

        parent::init();
    }
}