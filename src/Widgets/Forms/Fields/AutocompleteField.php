<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Override;
use Stringable;

/**
 * In hidden mode the value goes into a hidden input posting the attribute, the option's text into the visible one,
 * which has no name.
 */
class AutocompleteField extends InputField
{
    /**
     * Selected out of the endpoint's response, since the body's inherited `hx-select` would otherwise swap the whole
     * page on every keystroke.
     */
    final public const string OPTIONS_ID = 'autocomplete-options';

    /**
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $url = null;

    /**
     * Derived from the input's id, so a group's row template rewrites it with the name.
     */
    protected ?string $resultsId = null;

    protected bool $hidden = false;
    protected ?string $text = null;

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function url(array|string|null $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * The text the visible input shows for the value in hidden mode.
     */
    public function text(?string $text): static
    {
        $this->text = $text;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['autocomplete'] ??= 'off';

        // Assigned before the parent does, since the results id is derived from it.
        if ($this->model && $this->property) {
            $this->attributes['name'] ??= Html::getInputName($this->model, $this->property);
            $this->attributes['id'] ??= Html::getInputIdByName($this->attributes['name']);
        }

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

        if (!$this->hidden) {
            return Div::make()
                ->class('autocomplete')
                ->attribute('data-autocomplete', '')
                ->content(parent::getInput(), $this->getResults());
        }

        $value = Input::make()
            ->type('hidden')
            ->attribute('name', $this->attributes['name'] ?? null)
            ->attribute('value', $this->attributes['value'] ?? null)
            ->attribute('disabled', $this->attributes['disabled'] ?? null)
            ->attribute('data-autocomplete-value-input', true);

        $search = Input::make()
            ->attributes([...$this->attributes, 'name' => null, 'value' => $this->text])
            ->addClass('input');

        return Div::make()
            ->class('autocomplete')
            ->attribute('data-autocomplete', '')
            ->content($value, $search, $this->getResults());
    }

    protected function getResults(): Stringable
    {
        return Div::make()
            ->attribute('id', $this->getResultsId())
            ->class('dropdown-menu autocomplete-results')
            ->attribute('data-autocomplete-results', '')
            ->attribute('popover', 'manual')
            ->attribute('role', 'listbox')
            ->attribute('aria-label', $this->label);
    }

    protected function getResultsId(): string
    {
        if ($this->resultsId === null) {
            $id = $this->attributes['id'] ?? null;
            $this->resultsId = is_string($id) && $id !== '' ? "$id-results" : Html::getId();
        }

        return $this->resultsId;
    }

    /**
     * The query is not named here: `includes/autocomplete.ts` rewrites the input's own name, which carries the model
     * and attribute, to a single `q`.
     */
    protected function addAutocompleteAttributes(): void
    {
        $this->attributes = [
            ...$this->attributes,
            'hx-get' => is_array($this->url) ? Url::toRoute($this->url) : $this->url,
            'hx-push-url' => 'false',
            'hx-select' => '#' . self::OPTIONS_ID,
            // An empty attribute is what stops htmx from inheriting the body's out-of-band flash selector.
            'hx-select-oob' => '',
            'hx-swap' => 'innerHTML',
            'hx-target' => '#' . $this->getResultsId(),
            'hx-trigger' => 'input changed delay:250ms',
            'role' => 'combobox',
            'aria-autocomplete' => 'list',
            'aria-controls' => $this->getResultsId(),
            'aria-expanded' => 'false',
            'data-autocomplete-search' => true,
        ];
    }
}
