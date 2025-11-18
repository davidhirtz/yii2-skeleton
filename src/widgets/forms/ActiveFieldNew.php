<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;

class ActiveFieldNew extends Field
{
    use ContainerConfigurationTrait;
    use FormTrait;
    use PropertyWidgetTrait;
    use TagVisibilityTrait;
    use ModelWidgetTrait;

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
}