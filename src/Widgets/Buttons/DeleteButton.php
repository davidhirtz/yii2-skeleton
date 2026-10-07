<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Html\P;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Override;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

/**
 * Asks for the value of `$property` before it deletes, when one is set.
 *
 * @template TModel of Model
 */
class DeleteButton extends ConfirmButton
{
    /**
     * @use ModelTrait<TModel>
     */
    use ModelTrait;
    use PropertyTrait;

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
                ...Application::current()->getRequest()->getQueryParams(),
                'id' => $this->model->getPrimaryKey(),
            ];
        }

        $this->icon ??= 'trash';
        $this->style ??= 'danger';

        $this->label ??= Yii::t('yii', 'Delete');
        $this->title ??= Yii::t('yii', 'Are you sure you want to delete this item?');

        if ($this->property) {
            $this->message ??= Yii::t('skeleton', 'COMMON_TYPE_EXACT', [
                'attribute' => $this->model->getAttributeLabel($this->property),
            ]);
        }

        if ($this->message) {
            $this->addContent(P::make()->content($this->message));
        }

        parent::configure();
    }

    #[Override]
    protected function getForm(): ?Form
    {
        return $this->property
            ? Form::make()->content($this->getInput())
            : parent::getForm();
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
