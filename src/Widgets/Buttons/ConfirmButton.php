<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * @phpstan-type ButtonStyle 'accent'|'danger'|'link'|'primary'|'secondary'|'success'
 */
class ConfirmButton extends Widget
{
    use IconTrait;
    use LabelTrait;
    use TagContentTrait;
    use TitleTrait;
    use UrlTrait;

    protected ?string $confirmLabel = null;

    /**
     * @var ButtonStyle|null
     */
    protected ?string $style = null;

    /**
     * @var ButtonStyle|null
     */
    protected ?string $confirmStyle = null;

    protected ?Form $form = null;
    protected ?string $include = null;
    protected bool $iconOnly = false;
    protected bool $pushHistory = true;

    public function confirmLabel(?string $confirmLabel): static
    {
        $this->confirmLabel = $confirmLabel;
        return $this;
    }

    /**
     * @param ButtonStyle|null $style
     */
    public function style(?string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @param ButtonStyle|null $confirmStyle
     */
    public function confirmStyle(?string $confirmStyle): static
    {
        $this->confirmStyle = $confirmStyle;
        return $this;
    }

    public function danger(): static
    {
        return $this->style('danger');
    }

    public function form(?Form $form): static
    {
        $this->form = $form;
        return $this;
    }

    public function include(?string $include): static
    {
        $this->include = $include;
        return $this;
    }

    public function iconOnly(bool $iconOnly = true): static
    {
        $this->iconOnly = $iconOnly;
        return $this;
    }

    public function pushHistory(bool $pushHistory): static
    {
        $this->pushHistory = $pushHistory;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->style ??= 'primary';
        $this->confirmStyle ??= $this->style;
        $this->confirmLabel ??= $this->label;
        $this->title ??= $this->confirmLabel;

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getButton();
    }

    protected function getButton(): Button
    {
        $button = Button::make()
            ->addClass('btn btn-' . $this->style)
            ->icon($this->icon)
            ->modal($this->getModal());

        if ($this->iconOnly) {
            return $button
                ->ariaLabel($this->label)
                ->tooltip((string)$this->label);
        }

        return $button->text($this->label);
    }

    protected function getModal(): Modal
    {
        $button = $this->getConfirmButton();
        $form = $this->getForm();

        if ($form) {
            $button->attribute('form', $form->getId())->type('submit');
        }

        $target = $form ?? $button;

        if ($this->url !== null) {
            $target->attribute('hx-post', Url::to($this->url));
        }

        if ($this->pushHistory) {
            $target->attribute('hx-push-url', 'true')
                ->attribute('hx-swap', 'outerHTML show:top');
        }

        if ($this->include) {
            $target->attribute('hx-include', $this->include);
        }

        return Modal::make()
            ->title($this->title)
            ->content(...$this->content)
            ->addContent($form)
            ->footer($button);
    }

    protected function getConfirmButton(): Button
    {
        return Button::make()
            ->addClass('btn btn-' . $this->confirmStyle)
            ->text($this->confirmLabel);
    }

    protected function getForm(): ?Form
    {
        return $this->form;
    }
}
