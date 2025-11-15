<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\traits\TagCardTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormContainer extends Widget
{
    use ContainerWidgetTrait;
    use TagCardTrait;

    private string $form;

    /**
     * @todo this needs to be changed, once I have overhauled the form widgets
     */
    public function form(string $form): static
    {
        $this->form = $form;
        return $this;
    }

    protected function renderContent(): Stringable
    {
        return Card::make()
            ->title($this->title)
            ->collapsed($this->collapsed)
            ->content($this->form);
    }
}
