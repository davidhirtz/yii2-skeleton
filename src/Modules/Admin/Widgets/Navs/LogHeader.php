<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Yii;

class LogHeader extends Header
{
    /**
     * @use ProviderTrait<LogDataProvider>
     */
    use ProviderTrait;

    protected function configure(): void
    {
        $this->breadcrumbs ??= [
            new Breadcrumb(Lang::t('skeleton', 'COMMON_SYSTEM'), ['/admin/system/index']),
        ];

        $this->title ??= Lang::t('skeleton', 'COMMON_ERROR_LOGS');
        $this->url ??= ['/admin/log/index'];

        if ($this->provider) {
            $this->subtitle ??= $this->provider->file;

            $this->addContent(LogFileActionDropdown::make()
                ->file($this->provider->file));
        }

        parent::configure();
    }
}
