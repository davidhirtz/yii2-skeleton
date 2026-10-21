<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Stringable;
use Yii;

class LogHeader extends Header
{
    /**
     * @use ProviderTrait<LogDataProvider>
     */
    use ProviderTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'COMMON_ERROR_LOGS');
        $this->url ??= ['/admin/log/index'];

        if ($this->provider) {
            $this->subtitle ??= $this->provider->file;

            $this->addContent($this->getLogFileActionDropdown());
        }

        $this->addSystemBreadcrumb();

        parent::configure();
    }

    protected function getLogFileActionDropdown(): ?Stringable
    {
        return LogFileActionDropdown::make()->file($this->provider->file);
    }

    protected function addSystemBreadcrumb(): void
    {
        if (!$this->webuser->can(User::AUTH_ROLE_ADMIN) && !$this->webuser->can(User::AUTH_ROLE_MANAGER)) {
            return;
        }

        $this->addBreadcrumb(Yii::t('skeleton', 'COMMON_SYSTEM'), [
            '/admin/system/index',
        ]);
    }
}
