<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
