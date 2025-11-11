<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\assets;

use Override;
use Yii;

class AdminAsset extends AbstractAssetBundle
{
    public array $faviconOptions = [];

    public $css = ['css/admin.css'];
    public $depends = [FontAwesomeAsset::class];
    public $js = ['js/admin.js'];

    #[Override]
    public function init(): void
    {
        if (array_key_exists('href', $this->faviconOptions)) {
            $this->faviconOptions['rel'] ??= 'shortcut icon';
            Yii::$app->getView()->registerLinkTag($this->faviconOptions, 'favicon');
        }

        parent::init();
    }
}
