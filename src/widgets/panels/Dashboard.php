<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Dashboard extends Widget
{
    use ContainerWidgetTrait;

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
            ->class('row flex-wrap justify-center');

        foreach ($this->panels as $panel) {
            $list = ListGroup::make();

            foreach ($panel->items as $item) {
                $list->addItem(ListGroupItem::make()
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
