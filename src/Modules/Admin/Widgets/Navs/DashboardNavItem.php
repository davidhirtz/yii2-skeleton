<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Yii;

class DashboardNavItem extends NavItem
{
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('skeleton', 'Dashboard');
        $this->icon ??= 'home';
        $this->url ??= ['/admin/'];
        $this->order ??= 0;
        $this->visible = !Yii::$app->getUser()->getIsGuest();

        parent::__construct($config);
    }
}
