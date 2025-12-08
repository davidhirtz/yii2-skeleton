<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets;

use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagIconTrait;
use Override;
use Stringable;

class Alert extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;

    protected array $buttons = [];

    public function button(Button $btn): static
    {
        $this->buttons[] = $btn;
        return $this;
    }

    public function buttons(Button ...$buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    public function status(string $status): static
    {
        return $this->attribute('data-alert', $status);
    }

    public function success(): static
    {
        return $this->status('success');
    }

    public function info(): static
    {
        return $this->status('info');
    }

    public function warning(): static
    {
        return $this->status('warning');
    }

    public function danger(): static
    {
        return $this->status('danger');
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $alert = Div::make()
            ->attributes($this->attributes)
            ->addClass('alert');


        if ($this->icon) {
            $alert->addContent(
                Div::make()
                ->class('icon')
                ->content($this->icon),
            );
        }

        $alert->addContent(
            Div::make()
                ->class('alert-content')
                ->content(...$this->content),
        );

        if ($this->buttons) {
            $alert->addContent(Div::make()
                ->content(...$this->buttons)
                ->addClass('alert-buttons'));
        }

        return $alert;
    }
}
