<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Yii;

class Modal extends Tag
{
    use TagContentTrait;

    protected array $attributes = [
        'class' => 'modal',
    ];

    private ?string $title = null;
    private string|false|null $dismiss = null;
    private array $footer = [];

    public function dismiss(string|false|null $dismiss): static
    {
        $this->dismiss = $dismiss;
        return $this;
    }

    public function footer(Button|string $button): static
    {
        $this->footer[] = $button instanceof Button ? $button->attribute('data-modal', '') : $button;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = Html::encode($title);
        return $this;
    }

    protected function renderContent(): string
    {
        $content = [];

        if ($this->title || $this->dismiss !== false) {
            $header = [];

            if ($this->title) {
                $header[] = $this->title;
            }

            if ($this->dismiss !== false) {
                $header[] = Button::make()
                    ->attribute('aria-label', $this->dismiss ?? Yii::t('skeleton', 'Close'))
                    ->attribute('data-modal', '')
                    ->class('btn-icon')
                    ->icon('xmark');

                array_unshift($this->footer, Button::secondary(Yii::t('skeleton', 'Cancel'))
                    ->attribute('data-modal', '')
                    ->attribute('autofocus', true));
            }

            $content[] = Div::make()->class('modal-header')->html(...$header);
        }

        if ($this->content) {
            $content[] = Div::make()->class('modal-body')->html(...$this->content);
        }

        if ($this->footer) {
            $content[] = Div::make()->class('modal-footer')->html(...$this->footer);
        }

        return implode('', $content);
    }

    protected function getName(): string
    {
        return 'dialog';
    }
}
