<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Forms\Fields\AutocompleteField;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * The options an autocomplete endpoint answers with, swapped into the popover of an {@see AutocompleteField}. The
 * list carries {@see AutocompleteField::OPTIONS_ID} because the field selects it out of the response: the body's
 * inherited `hx-select` would otherwise find nothing and swap nothing.
 */
class AutocompleteList extends Widget
{
    use TagAttributesTrait;

    /**
     * @var list<array{text: string, value: int|string}>
     */
    protected array $options = [];

    protected ?string $emptyText = null;

    /**
     * @param list<array{text: string, value: int|string}> $options
     */
    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function emptyText(?string $emptyText): static
    {
        $this->emptyText = $emptyText;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $items = $this->options
            ? array_map($this->getOptionItem(...), $this->options)
            : [$this->getEmptyItem()];

        return Ul::make()
            ->attributes($this->attributes)
            ->attribute('id', AutocompleteField::OPTIONS_ID)
            ->addClass('dropdown-list')
            ->content(...$items);
    }

    /**
     * @param array{text: string, value: int|string} $option
     */
    protected function getOptionItem(array $option): Stringable
    {
        $button = Button::make()
            ->class('dropdown-option')
            ->attribute('data-autocomplete-value', (string)$option['value'])
            ->text($option['text']);

        return Li::make()
            ->class('dropdown-item')
            ->content($button);
    }

    protected function getEmptyItem(): Stringable
    {
        return Li::make()
            ->class('dropdown-option disabled')
            ->text($this->emptyText ?? Yii::t('skeleton', 'SEARCH_EMPTY'));
    }
}
