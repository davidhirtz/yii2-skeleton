<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Dialog;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
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
