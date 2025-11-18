<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\rows;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class FormRow extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    public array $headerAttributes = ['class' => 'col-form-label'];
    public array $contentAttributes = ['class' => 'col-form-content'];

    protected string|Stringable|null $header = null;

    public function header(string|Stringable|null $header): static
    {
        $this->header = $header;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $row = Div::make()
            ->attributes($this->attributes)
            ->addClass('form-group form-group-horizontal');

        if ($this->header) {
            $row->content(Div::make()
                ->attributes($this->headerAttributes)
                ->content($this->header));
        }

        return $row->addContent(Div::make()
            ->attributes($this->contentAttributes)
            ->content(...$this->content));
    }
}