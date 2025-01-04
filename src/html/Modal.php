<?php

namespace davidhirtz\yii2\skeleton\html;

use Yii;
use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\P;

final class Modal extends NormalTag
{
    protected array $attributes = [
        'class' => 'modal',
    ];

    private ?Div $title = null;
    private ?Div $body = null;
    private string|false|null $dismiss = null;
    private array $footer = [];

    private static int $counter = 0;

    public function body(string $html, array $attributes = []): self
    {
        $this->body ??= Div::tag()
            ->addClass('modal-body')
            ->encode(false);

        if ($attributes) {
            $this->body = $this->body->addAttributes($attributes);
        }

        $this->body = $this->body->content($html);

        return $this;
    }

    public function dismiss(string|false|null $dismiss): self
    {
        $this->dismiss = $dismiss;
        return $this;
    }

    public function getId(): string
    {
        return $this->attributes['id'] ??= 'modal-' . ++self::$counter;
    }

    public function text(string $text, array $attributes = []): self
    {
        $p = P::tag()->content($text);

        if ($attributes) {
            $p = $p->addAttributes($attributes);
        }

        return $this->body($p->render());
    }

    public function title(string $title): self
    {
        $this->title = Div::tag()
            ->addClass('modal-title')
            ->content($title);

        return $this;
    }

    public function footer(Btn|string $button): self
    {
        $this->footer[] = $button instanceof Btn ? $button->attribute('data-modal', '') : $button;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        $this->getId();
        parent::prepareAttributes();
    }

    protected function generateContent(): string
    {
        $html = '';

        if ($this->title || $this->dismiss !== false) {
            $header = Div::tag()->addClass('modal-header');

            if ($this->title) {
                $header = $header->addContent($this->title);
            }

            if ($this->dismiss !== false) {
                $header = $header->addContent(Button::button('')
                    ->addAttributes([
                        'aria-label' => $this->dismiss ?? Yii::t('skeleton', 'Close'),
                        'class' => 'btn-close',
                        'data-modal' => '',
                    ]));

                array_unshift($this->footer, Btn::secondary(Yii::t('skeleton', 'Cancel'))
                    ->attribute('data-modal', '')
                    ->attribute('autofocus', true));
            }

            $html .= $header->render();
        }

        if ($this->body) {
            $html .= $this->body->render();
        }

        if ($this->footer) {
            $footer = Div::tag()->addClass('modal-footer');

            foreach ($this->footer as $button) {
                $footer = $footer->addContent($button);
            }

            $html .= $footer->render();
        }

        return $html;
    }

    protected function getName(): string
    {
        return 'dialog';
    }
}
