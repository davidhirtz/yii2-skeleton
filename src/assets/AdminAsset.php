<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use Yii;
use yii\web\AssetBundle;

class AdminAsset extends AssetBundle
{
    /**
     * @var array contains the options for the favicon link ta
     */
    public array $faviconOptions = [];

    public $css = ['css/admin.css'];
    public $js = ['js/admin.js'];
    public $jsOptions = ['type' => 'module'];
    public $sourcePath = '@skeleton/assets/dist';

    public $depends = [
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
