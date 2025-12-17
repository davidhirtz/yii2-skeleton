<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Assets;

use Override;
use Yii;

class AdminAssetBundle extends AbstractAssetBundle
{
    public array $faviconOptions = [];

    public $css = ['css/admin.css'];
    public $depends = [FontAwesomeAssetBundle::class];
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
