<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\IconTextTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

class DeleteButton extends Widget
{
    use IconTextTrait;
    use LabelTrait;

    /**
     * @use ModelTrait<ActiveRecordInterface>
     */
    use ModelTrait;
    use PropertyTrait;
    use TitleTrait;
    use UrlTrait;
    use VisibilityTrait;

    protected string|null|false $message = null;

    public function message(string|null|false $message): static
    {
        $this->message = $message;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model instanceof ActiveRecordInterface) {
            $this->url ??= [
                'delete',
                ...Yii::$app->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }

        $this->icon ??= 'trash';

        $this->label ??= Yii::t('yii', 'Delete');
        $this->title ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->property) {
            $this->message ??= Yii::t('skeleton', 'Please type the exact {attribute} in the text field below to delete this record. All related files will also be unrecoverably deleted. This cannot be undone, please be certain!', [
                'attribute' => $this->model->getAttributeLabel($this->property),
            ]);
        }

        parent::configure();
    }

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

        if ($this->message) {
            $modal->addContent(P::make()->content($this->message));
        }

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
        $value = $this->model->{$this->property};

        return TextInput::make()
            ->autofocus()
            ->class('input')
            ->name('value')
            ->placeholder($this->model->getAttributeLabel($this->property))
            ->pattern($value ? ('^' . preg_quote($value, '/') . '$') : null)
            ->required();
    }
}
