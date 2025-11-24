<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Label;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\RowAttributesTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

abstract class Field extends Widget
{
    use FormWidgetTrait;
    use ModelWidgetTrait;
    use PropertyWidgetTrait;
    use RowAttributesTrait;
    use TagAttributesTrait;
    use TagVisibilityTrait;
    use TagIdTrait;
    use TagLabelTrait;

    public array $labelAttributes = [];

    public string $layout = '{input}{error}{hint}';

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

        if ($this->model && $this->property) {
            $this->error ??= $this->model->getFirstError($this->property);
            $this->hint ??= $this->model->getAttributeHint($this->property);

            $this->attributes['name'] ??= Html::getInputName($this->model, $this->property);
            $this->attributes['id'] ??= Html::getInputIdByName($this->attributes['name']);

            if ($this->model->isAttributeRequired($this->property)) {
                $this->attributes['required'] ??= true;
            }

            if ($this->model->hasErrors($this->property)) {
                $this->attributes['aria-invalid'] = true;
            }
        }

        $this->rowAttributes['id'] ??= "{$this->getId()}-row";

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $content = strtr($this->layout, [
            '{input}' => $this->getInput(),
            '{hint}' => $this->getHint(),
            '{error}' => $this->getError(),
        ]);

        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->header($this->getLabel())
            ->content($content);
    }

    protected function getLabel(): ?Label
    {
        return $this->label
            ? Label::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->for($this->getId())
                ->text($this->label)
            : null;
    }

    abstract protected function getInput(): string|Stringable;

    protected function getHint(): string|Stringable
    {
        return $this->hint
            ? Div::make()
                ->addClass('form-hint')
                ->text($this->hint)
            : '';
    }

    protected function getError(): string|Stringable
    {
        return $this->error
            ? Div::make()
                ->addClass('form-error')
                ->text($this->error)
            : '';
    }

    public function isSafe(): bool
    {
        return !$this->property || ($this->model?->isAttributeSafe($this->property) ?? false);
    }

    public function isRequired(): bool
    {
        return ($this->attributes['required'] ?? null) !== null;
    }
}
