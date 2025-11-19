<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Label;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\rows\FormRow;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Field extends Widget
{
    use FormTrait;
    use ModelWidgetTrait;
    use PropertyWidgetTrait;
    use TagAttributesTrait;
    use TagVisibilityTrait;
    use TagIdTrait;
    use TagLabelTrait;

    public array $rowAttributes = [];
    public array $labelAttributes = [];

    public function form(ActiveForm $form): static
    {
        $this->model ??= $form->getModel();
        $this->label ??= $this->model->getAttributeLabel($this->property);
        $this->form = $form;

        $this->attributes['name'] ??= Html::getInputName($this->model, $this->property);
        $this->attributes['id'] ??= Html::getInputIdByName($this->attributes['name']);
        $this->attributes['value'] ??= $this->model->{$this->property};

        if ($this->model->isAttributeRequired($this->property)) {
            $this->attributes['required'] ??= true;
        }

        if ($this->model->hasErrors($this->property)) {
            $this->attributes['aria-invalid'] = true;
        }

        $this->rowAttributes['id'] ??= "{$this->getId()}-field";

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->addClass(($this->attributes['required'] ?? false) ? 'required' : null)
            ->header($this->getLabel())
            ->content(
                $this->getInput(),
                $this->getError(),
                $this->getHint(),
            );
    }

    public function getLabel(): ?Label
    {
        return $this->label
            ? Label::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->for($this->getId())
                ->text($this->label)
            : null;
    }

    public function getInput(): Tag|VoidTag
    {
        $this->attributes['id'] ??= $this->getId();
        return $this->getInputTag();
    }

    protected function getInputTag(): Tag|VoidTag
    {
        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }

    public function getHint(): string|Stringable
    {
        return '';
    }

    public function getError(): string|Stringable
    {
        return '';
    }
}