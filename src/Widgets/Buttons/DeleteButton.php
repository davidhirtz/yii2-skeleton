<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Widgets\Buttons\Traits\DeleteButtonTrait;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class DeleteButton extends Widget
{
    use DeleteButtonTrait;
    use PropertyTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->isVisible() ? $this->getButton() : '';
    }

    protected function getButton(): Stringable
    {
        return Button::make()
            ->danger()
            ->text($this->label)
            ->icon($this->icon)
            ->modal($this->getModal());
    }

    protected function getModal(): Modal
    {
        $button = Button::make()
            ->danger()
            ->text($this->label);

        $modal = Modal::make()
            ->title($this->title)
            ->content(...$this->content)
            ->footer($button);

        if (!$this->property) {
            $button->post($this->url, true);
            return $modal;
        }

        $form = $this->getForm();
        $modal->addContent($form);
        $button->attribute('form', $form->getId())->type('submit');

        return $modal;
    }

    protected function getForm(): Form
    {
        return Form::make()
            ->attribute('hx-post', Url::toRoute($this->url))
            ->attribute('hx-swap', 'outerHTML show:window:top')
            ->content($this->getInput());
    }

    protected function getInput(): TextInput
    {
        return TextInput::make()
            ->autofocus()
            ->class('input')
            ->name('value')
            ->placeholder($this->model->getAttributeLabel($this->property))
            ->pattern('^' . preg_quote($this->model->{$this->property}, '/') . '$')
            ->required();
    }
}
