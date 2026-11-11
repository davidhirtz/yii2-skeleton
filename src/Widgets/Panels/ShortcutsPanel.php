<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Override;
use Stringable;
use Yii;

class ShortcutsPanel extends DashboardPanel
{
    /** @use ItemTrait<DashboardItem> */
    use ItemTrait;

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'DASHBOARD_SHORTCUTS_TITLE');
        $this->icon ??= 'bolt';

        parent::configure();
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        $items = array_values($this->items);
        usort($items, fn (DashboardItem $a, DashboardItem $b) => $a->getOrder() <=> $b->getOrder());

        $content = implode('', array_map(strval(...), $items));

        return $content !== '' ? Ul::make()->class('dashboard-list')->content($content) : '';
    }
}
