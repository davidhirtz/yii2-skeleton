<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTrait;
use Override;
use Stringable;

class Alert extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;

    protected array $buttons = [];
    private ?string $status = null;

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
        if ($status === 'error') {
            $status = 'danger';
        }

        $this->status = $status;
        return $this;
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
        if ($this->status) {
            Html::addCssClass($this->attributes, "alert-$this->status");
        }

        $alert = Div::make()
            ->attributes($this->attributes)
            ->addClass('alert');


        if ($this->icon) {
            $alert->addContent(Div::make()
                ->class('icon')
                ->content($this->icon),
            );
        }

        $alert->addContent(
            Div::make()
                ->class('grow')
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
