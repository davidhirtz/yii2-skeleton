<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagCardTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\panels\Card;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormContainer extends Widget
{
    use ContainerWidgetTrait;
    use TagCardTrait;

    /**
     * @todo use FormWidgetTrait once form-related methods are added
     */
    private string|ActiveForm $form;

    public function form(ActiveForm|string $form): static
    {
        $this->form = $form;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $this->title ??= $this->view->title;
        $content = (string)$this->form;

        return $content
            ? Card::make()
                ->title($this->title)
                ->collapsed($this->collapsed)
                ->content($content)
            : '';
    }
}
