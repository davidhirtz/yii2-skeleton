<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\CardTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class FormContainer extends Widget
{
    use ContainerTrait;
    use CardTrait;

    protected ActiveForm $form;

    public function form(ActiveForm|string $form): static
    {
        $this->form = $form;
        return $this;
    }

    #[Override]
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
