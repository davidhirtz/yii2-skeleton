<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
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
        return $this->addItems($this->getTrailItem(), $this->getRedirectsItem());
    }

    protected function getRedirectsItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'Redirects'))
            ->url(['/admin/redirect/index'])
            ->roles([Redirect::AUTH_REDIRECT_CREATE])
            ->routes(['admin/redirect']);
    }

    protected function getTrailItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'History'))
            ->url(['/admin/trail/index'])
            ->roles([Trail::AUTH_TRAIL_INDEX])
            ->routes(['admin/trail']);
    }
}
