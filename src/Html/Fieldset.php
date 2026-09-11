<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

class Fieldset extends Tag
{
    use TagContentTrait;

    protected ?Legend $legend = null;

    public function legend(string|Legend|null $legend): static
    {
        $this->legend = is_string($legend) ? Legend::make()->text($legend) : $legend;
        return $this;
    }

    #[Override]
    protected function before(): string
    {
        if ($this->legend !== null) {
            array_unshift($this->content, $this->legend);
        }

        return parent::before();
    }

    protected function getTagName(): string
    {
        return 'fieldset';
    }
}
