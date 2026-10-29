<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class DashboardNavItem extends NavItem
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('skeleton', 'DASHBOARD_LABEL');
        $this->icon ??= 'home';
        $this->url ??= ['/admin/dashboard/index'];
        $this->order ??= 0;
        $this->roles ??= [User::ROLE_AUTHENTICATED];

        $this->routes(['admin/dashboard/index']);

        parent::__construct($config);
    }

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && Module::current()->isDashboardAvailable();
    }
}
