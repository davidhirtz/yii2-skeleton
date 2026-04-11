<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagSelectTrait;
use Override;

class Select extends Tag
{
    use TagInputTrait;
    use TagSelectTrait;

    public function size(int $size): static
    {
        $this->attributes['size'] = $size;
        return $this;
    }

    public function multiple(): static
    {
        $this->attributes['multiple'] = true;
        return $this;
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'select';
    }
}
