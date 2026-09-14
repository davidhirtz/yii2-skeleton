<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Traits\CollapsedTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * A card of label / value rows, each with an optional action button. It borrows the form row classes rather than a
 * table: unlike a grid it has no data provider, no sorting and no pagination, because the rows are facts about the
 * installation rather than records.
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
                ->class('form-hint')
                ->addText($hint));
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->rows) {
            return '';
        }

        $content = Div::make()->class('form-rows');

        foreach ($this->rows as $row) {
            $content->addContent(FormRow::make()
                ->header($row['label'])
                ->content($this->getRowContent($row['value'], $row['action'])));
        }

        return Card::make()
            ->title($this->title)
            ->collapsed($this->collapsed)
            ->content($content);
    }

    protected function getRowContent(
        string|Stringable $value,
        string|Stringable|null $action,
    ): string|Stringable {
        return $action === null
            ? $value
            : Div::make()
                ->class('form-action')
                ->addContent(Div::make()->content($value))
                ->addContent($action);
    }
}
