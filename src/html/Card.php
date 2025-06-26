<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Yii;

class Card extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'card',
    ];

    protected ?bool $collapsed = null;
    protected ?string $title = null;

    public function danger(): static
    {
        return $this->addClass('card-danger');
    }

    public function title(string|null $title): static
    {
        $this->title = $title ? Html::encode($title) : null;
        return $this;
    }

    public function collapsed(?bool $collapsed): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        if ($this->collapsed !== null) {
            $this->attribute('aria-expanded', $this->collapsed);

            if ($this->collapsed) {
                $this->addClass('collapsed');
            }

            $this->getId();
        }

        parent::prepareAttributes();
    }

    #[\Override]
    protected function renderContent(): string
    {
        $content = [];

        if ($this->title) {
            $header = [];

            if ($this->collapsed !== null) {
                $header[] = Button::make()
                    ->attribute('data-collapse', '#' . $this->getId())
                    ->class('btn btn-link')
                    ->html($this->title);

                $header[] = Button::make()
                    ->attribute('aria-label', Yii::t('skeleton', 'Toggle'))
                    ->attribute('data-collapse', '#' . $this->getId())
                    ->class('btn-collapse', 'btn-icon')
                    ->icon('chevron-down');
            } else {
                $header[] = $this->title;
            }

            $content[] = Div::make()
                ->class('card-header')
                ->html(Div::make()
                    ->class('card-title')
                    ->html(...$header));
        }

        $content[] = Div::make()
            ->class('card-body')
            ->html(...$this->content);

        return implode('', $content);
    }
}
