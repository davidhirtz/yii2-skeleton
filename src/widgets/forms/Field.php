<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\FormTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Field extends Widget
{
    use TagLabelTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    public array $labelAttributes = [];
    public array $contentAttributes = [];

    public array $inputAttributes = [];

    protected mixed $value = null;

    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return $this->model->getAttributeLabel($this->property);
    }
}