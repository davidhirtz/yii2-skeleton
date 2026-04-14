<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

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
            Yii::t('skeleton', 'System') => ['/admin/system/index'],
        ];

        $this->title ??= Yii::t('skeleton', 'Error logs');
        $this->url ??= ['/admin/log/index'];

        if ($this->provider) {
            $this->subtitle ??= $this->provider->file;

            $this->addContent(LogFileActionDropdown::make()
                ->file($this->provider->file));
        }

        parent::configure();
    }
}
