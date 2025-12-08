<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms;

use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagIdTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagVisibilityTrait;
use Hirtz\Skeleton\widgets\forms\traits\RowAttributesTrait;
use Hirtz\Skeleton\widgets\traits\FormatTrait;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
use Hirtz\Skeleton\widgets\traits\PropertyWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
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

    #[\Override]
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
