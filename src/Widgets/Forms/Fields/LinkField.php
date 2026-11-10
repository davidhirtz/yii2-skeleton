<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\InputGroup;
use Override;
use Stringable;
use Yii;

/**
 * A picked suggestion (`entry:12`) shows by its name while the input stays in the form, hidden, posting it.
 */
class LinkField extends AutocompleteField
{
    protected ?string $selectedText = null;
    protected ?string $selectedDescription = null;

    /**
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $selectedUrl = null;

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function selected(?string $text, ?string $description = null, array|string|null $url = null): static
    {
        $this->selectedText = $text;
        $this->selectedDescription = $description;
        $this->selectedUrl = $url;

        return $this;
    }

    /**
     * A `prepare()` closure may name the URL after the parent decided on the request attributes.
     */
    #[Override]
    protected function configure(): void
    {
        parent::configure();

        if ($this->url && !isset($this->attributes['hx-get'])) {
            $this->addAutocompleteAttributes();
        }
    }

    #[Override]
    protected function getControl(): string|Stringable
    {
        $control = parent::getControl();

        if (!$this->url && $this->selectedText === null) {
            return $control;
        }

        $isSelected = $this->selectedText !== null;

        return Div::make()
            ->class('link-field')
            ->attribute('data-link-field', true)
            ->content(
                $this->getSelected()->attribute('hidden', !$isSelected),
                Div::make()
                    ->class('link-field-input')
                    ->attribute('hidden', $isSelected)
                    ->content($control),
            );
    }

    protected function getSelected(): InputGroup
    {
        // An anchor even without a URL, so the script can give it the one a pick brings.
        $name = A::make()->target('_blank');

        if ($this->selectedUrl !== null) {
            $name->href($this->selectedUrl);
        }

        $name->addClass('input link-field-name')
            ->attribute('data-link-field-name', true)
            ->content(
                Span::make()
                    ->attribute('data-link-field-text', true)
                    ->text($this->selectedText ?? ''),
                Span::make()
                    ->class('text-muted')
                    ->attribute('data-link-field-description', true)
                    ->text($this->selectedDescription ?? ''),
            );

        $label = Yii::t('skeleton', 'LINK_CLEAR_TOOLTIP');

        $clear = Button::make()
            ->class('btn')
            ->type('button')
            ->icon('xmark')
            ->tooltip($label)
            ->attribute('aria-label', $label)
            ->attribute('data-link-field-clear', true)
            ->attribute('disabled', $this->attributes['disabled'] ?? null);

        return InputGroup::make()
            ->addClass('link-field-selected')
            ->append($clear)
            ->content($name);
    }
}
