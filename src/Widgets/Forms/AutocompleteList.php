<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Forms\Fields\AutocompleteField;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * Carries {@see AutocompleteField::OPTIONS_ID}, since the field selects it out of the response; `includes/combobox.ts`
 * gives the options their ids.
 */
class AutocompleteList extends Widget
{
    use TagAttributesTrait;

    /**
     * @var list<array{text: string, value: int|string, description?: string|null, url?: string|null}>
     */
    protected array $options = [];

    protected ?string $emptyText = null;

    /**
     * @param list<array{text: string, value: int|string, description?: string|null, url?: string|null}> $options
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
            ->attribute('role', 'none')
            ->addClass('dropdown-list')
            ->content(...$items);
    }

    /**
     * @param array{text: string, value: int|string, description?: string|null, url?: string|null} $option
     */
    protected function getOptionItem(array $option): Stringable
    {
        $button = Button::make()
            ->class('dropdown-option')
            ->attribute('data-autocomplete-value', (string)$option['value'])
            ->attribute('data-autocomplete-text', $option['text'])
            ->attribute('data-autocomplete-url', $option['url'] ?? null)
            ->attribute('role', 'option')
            ->attribute('tabindex', '-1');

        $description = $option['description'] ?? null;

        if ($description !== null && $description !== '') {
            $button->content(
                Div::make()->text($option['text']),
                Div::make()->class('small')->text($description),
            );
        } else {
            $button->text($option['text']);
        }

        return Li::make()
            ->class('dropdown-item')
            ->attribute('role', 'none')
            ->content($button);
    }

    protected function getEmptyItem(): Stringable
    {
        return Li::make()
            ->class('dropdown-option disabled')
            ->attribute('role', 'option')
            ->attribute('aria-disabled', 'true')
            ->text($this->emptyText ?? Yii::t('skeleton', 'SEARCH_EMPTY'));
    }
}
