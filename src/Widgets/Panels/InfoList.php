<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Table;
use Hirtz\Skeleton\Html\Tbody;
use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Html\Th;
use Hirtz\Skeleton\Html\Tr;
use Hirtz\Skeleton\Widgets\Traits\CollapsedTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * A card of label / value pairs, each with an optional action button. Unlike a grid it has no data provider, no
 * sorting and no pagination: the rows are facts about the installation rather than records.
 */
class InfoList extends Widget
{
    use ContainerTrait;
    use CollapsedTrait;
    use TitleTrait;

    /**
     * @var list<array{label: string|Stringable, value: string|Stringable, action: string|Stringable|null}>
     */
    protected array $rows = [];

    public function addRow(
        string|Stringable $label,
        string|Stringable|null $value,
        string|Stringable|null $action = null,
    ): static {
        $this->rows[] = [
            'label' => $label,
            'value' => $value ?? '',
            'action' => $action,
        ];

        return $this;
    }

    /**
     * @param Closure(static): (void|static) $rows a closure, so a listener on `EVENT_CONFIGURE` can add to the rows
     * another bundle contributed.
     */
    public function rows(Closure $rows): static
    {
        return $this->prepare($rows);
    }

    protected function getValue(string|Stringable $value, string|Stringable|null $hint): string|Stringable
    {
        if ($hint === null || (string)$hint === '') {
            return $value;
        }

        return Div::make()
            ->addContent(Div::make()->addText($value))
            ->addContent(Div::make()
                ->class('small')
                ->addText($hint));
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->rows) {
            return '';
        }

        $hasActions = (bool)array_filter(array_column($this->rows, 'action'));
        $body = Tbody::make();

        foreach ($this->rows as $row) {
            $cells = [
                Th::make()
                    ->attribute('scope', 'row')
                    ->content($row['label']),
                Td::make()->content($row['value']),
            ];

            if ($hasActions) {
                $cells[] = Td::make()
                    ->class('table-action')
                    ->content($row['action']);
            }

            $body->addRows(Tr::make()->cells(...$cells));
        }

        return Card::make()
            ->title($this->title)
            ->collapsed($this->collapsed)
            ->content(Div::make()
                ->class('table-wrap')
                ->content(Table::make()
                    ->class('table table-data')
                    ->body($body)));
    }
}
