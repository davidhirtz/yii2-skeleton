<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Label;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\forms\rows\FormRow;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class Field extends Widget
{
    use TagLabelTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    public array $rowAttributes = [];
    public array $labelAttributes = [];

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
        return '';
    }

    public function getError(): string|Stringable
    {
        return '';
    }
}