<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagCardTrait;
use davidhirtz\yii2\skeleton\widgets\panels\Card;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormContainer extends Widget
{
    use ContainerWidgetTrait;
    use TagCardTrait;

    private string|ActiveForm $form;

    /**
     * @todo this needs to be changed, once I have overhauled the form widgets
     */
    public function form(string|ActiveForm $form): static
    {
        $this->form = $form;
        return $this;
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
