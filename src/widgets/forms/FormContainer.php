<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms;

use Hirtz\Skeleton\html\traits\TagCardTrait;
use Hirtz\Skeleton\widgets\forms\traits\FormWidgetTrait;
use Hirtz\Skeleton\widgets\panels\Card;
use Hirtz\Skeleton\widgets\traits\ContainerWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

class FormContainer extends Widget
{
    use ContainerWidgetTrait;
    use TagCardTrait;

    private ActiveForm $form;

    public function form(ActiveForm|string $form): static
    {
        $this->form = $form;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->title ??= $this->view->title;
        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $content = (string)$this->form;

        return $content
            ? Card::make()
                ->title($this->title)
                ->collapsed($this->collapsed)
                ->content($content)
            : '';
    }
}
