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
        'tabindex' => -1,
    ];

    private ?Div $title = null;
    private ?Div $body = null;
    private string|false|null $dismiss = null;
    private array $footer = [];

    public function body(string $html): self
    {
        $this->body ??= Div::tag()
            ->addClass('modal-body')
            ->encode(false);

        $this->body = $this->body->content($html);

        return $this;
    }

    public function dismiss(string|false|null $dismiss): self
    {
        $this->dismiss = $dismiss;
        return $this;
    }

    public function text(string $text): self
    {
        return $this->body(P::tag()->content($text)->render());
    }

    public function title(string $title): self
    {
        $this->title = Div::tag()
            ->addClass('modal-title')
            ->content($title);

        return $this;
    }

    public function action(NormalTag|string $button): self
    {
        $this->footer[] = $button instanceof Btn ? $button->attribute('data-bs-dismiss', 'modal') : $button;
        return $this;
    }

    protected function generateContent(): string
    {
        $content = Div::tag()->addClass('modal-content');

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
                        'data-bs-dismiss' => 'modal',
                    ]));

                array_unshift($this->footer, Btn::secondary(Yii::t('skeleton', 'Cancel'))
                    ->attribute('data-bs-dismiss', 'modal'));
            }

            $content = $content->addContent($header);
        }

        if ($this->body) {
            $content = $content->addContent($this->body);
        }

        if ($this->footer) {
            $footer = Div::tag()->addClass('modal-footer');

            foreach ($this->footer as $button) {
                $footer = $footer->addContent($button);
            }

            $content = $content->addContent($footer);
        }

        return Div::tag()
            ->addClass('modal-dialog modal-dialog-centered modal-dialog-scrollable')
            ->addContent($content);
    }

    protected function getName(): string
    {
        return 'div';
    }
}
