<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagLabelTrait;
use Override;
use Stringable;

class Option extends Tag
{
    use TagLabelTrait;

    public function disabled(bool $disabled = true): static
    {
        return $this->attribute('disabled', $disabled ? true : null);
    }

    public function selected(bool $selected = true): static
    {
        return $this->attribute('selected', $selected ? true : null);
    }

    public function value(string|int|float|null $value): static
    {
        $this->attributes['value'] = $value;
        return $this;
    }

    #[\Override]
    protected function renderContent(): string|Stringable
    {
        return (string)$this->label;
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'option';
    }
}
