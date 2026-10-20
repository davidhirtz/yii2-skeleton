<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Widgets\Forms\FormText;
use Override;
use Stringable;

class FormTextField extends Field
{
    protected string|Stringable|null $content = null;

    public function content(string|Stringable|null $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Posts nothing, so a fieldset keeps it although its attribute is unsafe.
     */
    #[Override]
    public function isDisabled(): bool
    {
        return true;
    }

    #[Override]
    public function isVisible(): bool
    {
        return $this->content !== null && (string)$this->content !== '' && parent::isVisible();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return FormText::make()
            ->model($this->model)
            ->property($this->property)
            ->content($this->content);
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        return '';
    }
}
