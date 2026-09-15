<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Forms\AutocompleteList;
use Override;
use Stringable;

/**
 * An input whose suggestions an endpoint renders as an {@see AutocompleteList} and htmx swaps into a popover below
 * it. Picking one writes its value into the input, so the endpoint decides what the field holds.
 */
class AutocompleteField extends InputField
{
    /**
     * The id the field selects out of the endpoint's response; the body's inherited `hx-select` would otherwise
     * swap the whole page on every keystroke.
     */
    final public const string OPTIONS_ID = 'autocomplete-options';

    /**
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $url = null;

    /**
     * Not the input's id: it is read before {@see Field::configure()} assigns that one from the attribute name.
     */
    protected ?string $resultsId = null;

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function url(array|string|null $url): static
    {
        $this->url = $url;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['autocomplete'] ??= 'off';

        if ($this->url) {
            $this->addAutocompleteAttributes();
        }

        parent::configure();
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        if (!$this->url) {
            return parent::getInput();
        }

        return Div::make()
            ->class('autocomplete')
            ->attribute('data-autocomplete', '')
            ->content(parent::getInput(), $this->getResults());
    }

    protected function getResults(): Stringable
    {
        return Div::make()
            ->attribute('id', $this->getResultsId())
            ->class('dropdown-menu autocomplete-results')
            ->attribute('data-autocomplete-results', '')
            ->attribute('popover', 'manual');
    }

    protected function getResultsId(): string
    {
        return $this->resultsId ??= Html::getId();
    }

    /**
     * The endpoint answers with the option list alone. The query is not named here: the input's own name carries
     * the model and attribute, so `includes/autocomplete.ts` rewrites the parameters to a single `q`.
     */
    protected function addAutocompleteAttributes(): void
    {
        $this->attributes = [
            ...$this->attributes,
            'hx-get' => is_array($this->url) ? Url::toRoute($this->url) : $this->url,
            'hx-push-url' => 'false',
            'hx-select' => '#' . self::OPTIONS_ID,
            // Only the literal `unset` stops htmx from inheriting the body's out-of-band flash selector.
            'hx-select-oob' => 'unset',
            'hx-swap' => 'innerHTML',
            'hx-target' => '#' . $this->getResultsId(),
            'hx-trigger' => 'input changed delay:250ms',
        ];
    }
}
