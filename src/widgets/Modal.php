<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Dialog;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use Override;
use Stringable;
use Yii;

class Modal extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIdTrait;
    use TagTitleTrait;

    private string|false|null $dismiss = null;
    private array $footer = [];

    public function dismiss(string|false|null $dismiss): static
    {
        $this->dismiss = $dismiss;
        return $this;
    }

    public function footer(Stringable|string $button): static
    {
        $this->footer[] = $button;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $dialog = Dialog::make()
            ->attributes($this->attributes)
            ->addClass('modal');

        if ($this->title || $this->dismiss !== false) {
            $header = Div::make()
                ->class('modal-header');

            if ($this->title) {
                $header->addContent(Div::make()
                    ->addClass('modal-title')
                    ->text($this->title));
            }

            if ($this->dismiss !== false) {
                $header->addContent(Button::make()
                    ->attribute('aria-label', $this->dismiss ?? Yii::t('skeleton', 'Close'))
                    ->attribute('data-modal', '')
                    ->class('btn-icon')
                    ->icon('xmark'));

                array_unshift($this->footer, Button::make()
                    ->secondary()
                    ->text(Yii::t('skeleton', 'Cancel'))
                    ->attribute('data-modal', '')
                    ->attribute('autofocus', true));
            }

            $dialog->addContent($header);
        }

        if ($this->content) {
            $dialog->addContent(Div::make()
                ->class('modal-body')
                ->content(...$this->content));
        }

        if ($this->footer) {
            $dialog->addContent(Div::make()
                ->class('modal-footer')
                ->content(...$this->footer));
        }

        return $dialog;
    }
}
