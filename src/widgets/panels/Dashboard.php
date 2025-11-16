<?php

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Dashboard extends Widget
{
    use ContainerWidgetTrait;

    /**
     * @var array<string, DashboardPanel|array>
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
            if (!$panel instanceof DashboardPanel) {
                $panel = new DashboardPanel(...$panel);
            }

            $list = ListGroup::make();

            foreach ($panel->items as $item) {
                $list->addLink(ListGroupItemLink::make()
                    ->attributes($item->attributes)
                    ->text($item->label)
                    ->href($item->url)
                    ->roles($item->roles)
                    ->icon($item->icon));
            }

            $wrap->addContent(Div::make()
                ->attributes($panel->attributes)
                ->addClass('dashboard-item')
                ->content(Card::make()
                    ->addClass('dashboard-card')
                    ->title($panel->title)
                    ->content($list)));
        }

        return $wrap;
    }
}