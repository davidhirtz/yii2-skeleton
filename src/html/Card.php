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

    protected ?bool $collapse = null;
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

    public function collapse(?bool $collapse): static
    {
        $this->collapse = $collapse;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        if ($this->collapse !== null) {
            $this->attribute('aria-expanded', $this->collapse);

            if ($this->collapse) {
                $this->addClass('collapsed');
            }

            $this->getId();
        }

        parent::prepareAttributes();
    }

    protected function renderContent(): string
    {
        $content = [];

        if ($this->title) {
            $header = [];

            if ($this->collapse !== null) {
                $header[] = Button::make()
                    ->attribute('data-collapse', '#' . $this->getId())
                    ->class('btn btn-link')
                    ->html($this->title);

                $header[] = Button::make()
                    ->attribute('aria-label', Yii::t('skeleton', 'Toggle'))
                    ->attribute('data-collapse', $this->getId())
                    ->class('btn-collapse');
            } else {
                $header[] = $this->title;
            }

            $content[] = '<div class="card-header"><div class="card-title">' . implode('', $header) . '</div></div>';
        }

        $content[] = '<div class="card-body">' . implode('', $this->content) . '</div>';

        return implode('', $content);
    }
}
