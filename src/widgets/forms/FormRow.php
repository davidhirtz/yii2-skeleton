<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms;

use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

class FormRow extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    public array $headerAttributes = ['class' => 'form-label'];
    public array $contentAttributes = ['class' => 'form-content'];

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
            ->addClass('form-group form-row');

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
