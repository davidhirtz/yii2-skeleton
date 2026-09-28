<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\CardTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class FormContainer extends Widget
{
    use ContainerTrait {
        render as renderInContainer;
    }
    use CardTrait;

    protected ActiveForm|string $form;

    private ?string $formHtml = null;

    public function form(ActiveForm|string $form): static
    {
        $this->form = $form;
        return $this;
    }

    /**
     * The form's language tabs stand in a container of their own before the card, aligned with the submenu above.
     */
    #[Override]
    public function render(bool $refresh = false): string
    {
        if ($this->form instanceof ActiveForm) {
            $this->form->inlineTranslationTabs = false;
        }

        $this->formHtml = (string)$this->form;

        $tabs = $this->form instanceof ActiveForm ? $this->form->getTranslationTabs() : '';

        return ($tabs ? Container::make()->content($tabs)->render() : '') . $this->renderInContainer($refresh);
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $content = $this->formHtml ?? (string)$this->form;

        return $content
            ? Card::make()
                ->title($this->title)
                ->collapsed($this->collapsed)
                ->content($content)
            : '';
    }
}
