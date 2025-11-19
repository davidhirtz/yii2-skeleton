<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Label;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
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
    use TagInputTrait;
    use TagVisibilityTrait;
    use TagIdTrait;
    use TagLabelTrait;

    public array $rowAttributes = [];
    public array $labelAttributes = [];

    protected ?string $error = null;
    protected ?string $hint = null;

    public function error(?string $error): static
    {
        $this->error = $error;
        return $this;
    }

    public function hint(?string $hint): static
    {
        $this->hint = $hint;
        return $this;
    }

    public function form(ActiveForm $form): static
    {
        $this->model ??= $form->model;
        $this->label ??= $this->model->getAttributeLabel($this->property);
        $this->form = $form;

        $this->error ??= $this->model->getFirstError($this->property);
        $this->hint ??= $this->model->getAttributeHint($this->property);

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

        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }

    public function getHint(): string|Stringable
    {
        return $this->hint
            ? Div::make()
                ->addClass('form-hint')
                ->text($this->hint)
            : '';
    }

    public function getError(): string|Stringable
    {
        return $this->error
            ? Div::make()
                ->addClass('form-error')
                ->text($this->error)
            : '';
    }

    protected function isRequired(): bool
    {
        return ($this->attributes['required'] ?? false) !== null;
    }
}