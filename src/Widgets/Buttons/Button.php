<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagLinkTrait;
use Hirtz\Skeleton\Widgets\Buttons\Traits\AjaxAttributesTrait;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\TooltipAttributeTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Button extends Widget
{
    use TagAttributesTrait;
    use AjaxAttributesTrait;
    use IconTextTrait;
    use TagInputTrait;
    use TagLinkTrait;
    use TooltipAttributeTrait;

    protected ?Modal $modal = null;

    public function modal(?Modal $modal): static
    {
        $this->modal = $modal;
        return $this->type('button');
    }

    public function danger(): static
    {
        return $this->addClass('btn btn-danger');
    }

    public function primary(): static
    {
        return $this->addClass('btn btn-primary');
    }

    public function success(): static
    {
        return $this->addClass('btn btn-success');
    }

    public function secondary(): static
    {
        return $this->addClass('btn btn-secondary');
    }

    public function link(): static
    {
        return $this->addClass('btn btn-link');
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->modal) {
            $this->attributes['data-modal'] ??= '#' . $this->modal->getId();
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->modal . $this->renderTag();
    }

    protected function renderTag(): string|Stringable
    {
        return (($this->attributes['href'] ?? null) ? A::make() : \Hirtz\Skeleton\Html\Button::make())
            ->attributes($this->attributes)
            ->content($this->getIconText());
    }
}
