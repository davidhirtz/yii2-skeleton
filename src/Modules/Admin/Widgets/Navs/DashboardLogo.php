<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Img;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class DashboardLogo extends Widget
{
    use TagAttributesTrait;

    public const string DEFAULT_LOGO_SRC = '/images/admin/logo.svg';

    private string|null|false $logoSrc = null;

    #[Override]
    protected function configure(): void
    {
        $this->attributes['src'] ??= $this->getDefaultImageSource();
        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return array_key_exists('src', $this->attributes)
            ? Img::make()->attributes($this->attributes)
            : '';
    }

    protected function getDefaultImageSource(): string|false
    {
        if ($this->logoSrc === null) {
            $path = Yii::getAlias('@webroot') . static::DEFAULT_LOGO_SRC;
            $this->logoSrc = file_exists($path) ? static::DEFAULT_LOGO_SRC : false;
        }

        return $this->logoSrc;
    }
}
