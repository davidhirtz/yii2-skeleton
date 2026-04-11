<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class SystemNavItem extends NavItem
{
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('skeleton', 'System');
        $this->icon ??= 'cogs';
        $this->order ??= 999;
        $this->url ??= ['/admin/system/index'];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->addDefaultItems();
        parent::configure();
    }

    protected function addDefaultItems(): static
    {
        return $this->addItem($this->getLogIndexItem(), $this->getTrailIndexItem(), $this->getRedirectIndexItem());
    }

    protected function getLogIndexItem(): NavItem
    {
        return NavItem::make()
            ->icon('server')
            ->label(Yii::t('skeleton', 'Error logs'))
            ->url(['/admin/log/index'])
            ->roles([User::AUTH_ROLE_ADMIN])
            ->routes(['admin/log']);
    }

    protected function getRedirectIndexItem(): NavItem
    {
        return NavItem::make()
            ->icon('forward')
            ->label(Yii::t('skeleton', 'Redirects'))
            ->url(['/admin/redirect/index'])
            ->roles([Redirect::AUTH_REDIRECT_CREATE])
            ->routes(['admin/redirect']);
    }

    protected function getTrailIndexItem(): NavItem
    {
        return NavItem::make()
            ->icon('history')
            ->label(Yii::t('skeleton', 'History'))
            ->url(['/admin/trail/index'])
            ->roles([Trail::AUTH_TRAIL_INDEX])
            ->routes(['admin/trail']);
    }
}
