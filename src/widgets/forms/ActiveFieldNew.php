<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use Stringable;

class ActiveFieldNew extends Field
{
    use FormTrait;
    use ModelWidgetTrait;
    use PropertyWidgetTrait;
    use TagVisibilityTrait;

    protected function renderContent(): string|Stringable
    {
//        $this->label ??= $this->model->getAttributeLabel($this->property);
//        $this->value ??= $this->model->{$this->property};

        return $this->model->getAttributeLabel($this->property);
    }
}