<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Dashboard extends Widget
{
    use ContainerTrait;

    /**
     * @var array<string, DashboardPanel>
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
            $wrap->addContent(Div::make()
                ->attributes($panel->attributes)
                ->addClass('dashboard-item')
                ->content(Card::make()
                    ->addClass('dashboard-card')
                    ->title($panel->name)
                    ->content($panel)));
        }

        return $wrap;
    }
}
