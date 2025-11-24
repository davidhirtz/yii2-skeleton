<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\forms\traits\RowAttributesTrait;
use davidhirtz\yii2\skeleton\widgets\traits\FormatTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\traits\PropertyWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormText extends Widget
{
    use FormatTrait;
    use ModelWidgetTrait;
    use PropertyWidgetTrait;
    use RowAttributesTrait;
    use TagAttributesTrait;
    use TagVisibilityTrait;
    use TagIdTrait;
    use TagLabelTrait;

    public array $contentAttributes = [];
    public array $labelAttributes = [];

    protected string|int|float|bool|Stringable|null $content = null;

    public function content(string|int|float|bool|Stringable|null $content): static
    {
        $this->content = $content;
        return $this;
    }

    protected function configure(): void
    {
        $this->label ??= $this->model->getAttributeLabel($this->property);
        $this->content ??= $this->model->{$this->property};

        if ($this->model && $this->property) {
            $this->attributes['id'] ??= Html::getInputId($this->model, $this->property);
        }

        $this->rowAttributes['id'] ??= "{$this->getId()}-row";

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->header(Div::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->content($this->label))
            ->content(Div::make()
                ->attributes($this->contentAttributes)
                ->addClass('form-text')
                ->content($this->formatValue($this->content)));
    }
}
