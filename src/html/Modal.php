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
                $header[] = Button::make()->attributes([
                    'aria-label' => $this->dismiss ?? Yii::t('skeleton', 'Close'),
                    'class' => 'btn-close',
                    'data-modal' => '',
                ]);

                array_unshift($this->footer, Button::secondary(Yii::t('skeleton', 'Cancel'))
                    ->attribute('data-modal', '')
                    ->attribute('autofocus', true));
            }

            $content[] = '<div class="modal-header">' . implode('', $header) . '</div>';
        }

        if ($this->content) {
            $content[] = '<div class="modal-body">' . implode('', $this->content) . '</div>';
        }

        if ($this->footer) {
            $content[] = '<div class="modal-footer">' . implode('', $this->footer) . '</div>';
        }

        return implode('', $content);
    }

    protected function getName(): string
    {
        return 'dialog';
    }
}
