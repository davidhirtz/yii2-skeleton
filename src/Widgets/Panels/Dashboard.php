<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardPanelConfig;
use Hirtz\Skeleton\Widgets\Traits\ContainerWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Dashboard extends Widget
{
    use ContainerWidgetTrait;

    /**
     * @var array<string, DashboardPanelConfig>
     */
    protected array $panels;

    public function panels(array $panels): static
    {
        $this->panels = $panels;
        return $this;
    }

    protected function renderContent(): Stringable
    {
        $wrap = Div::make()
            ->class('dashboard');

        foreach ($this->panels as $panel) {
            $list = Stack::make();

            foreach ($panel->items as $item) {
                $list->addItem(StackItem::make()
                    ->attributes($item->attributes)
                    ->label($item->label)
                    ->url($item->url)
                    ->roles($item->roles)
                    ->icon($item->icon));
            }

            $wrap->addContent(Div::make()
                ->attributes($panel->attributes)
                ->addClass('dashboard-item')
                ->content(Card::make()
                    ->addClass('dashboard-card')
                    ->title($panel->name)
                    ->content($list)));
        }

        return $wrap;
    }
}
