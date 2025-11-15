<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use Stringable;

class Panel implements Stringable
{
    final public const string TYPE_DEFAULT = 'default';
    final public const string TYPE_DANGER = 'danger';

    public ?string $content = null;
    public ?bool $collapsed = null;
    public ?string $id = null;
    public ?string $title = null;
    public string $type = self::TYPE_DEFAULT;

    public function content(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function collapsed(?bool $collapsed): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function title(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function render(): string
    {
        return $this->content
            ? Container::make()
                ->attribute('id', $this->id)
                ->html($this->renderContent())
                ->render()
            : '';
    }

    protected function renderContent(): string
    {
        $card = Card::make()
            ->title($this->title)
            ->html($this->content)
            ->collapsed($this->collapsed);

        if ($this->type === self::TYPE_DANGER) {
            $card->danger();
        }

        return $card->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
