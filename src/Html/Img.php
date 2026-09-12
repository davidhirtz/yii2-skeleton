<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\VoidTag;
use Hirtz\Skeleton\Html\Traits\TagImageAttributesTrait;
use Override;

class Img extends VoidTag
{
    use TagImageAttributesTrait;

    public array $attributes = [
        'alt' => '',
    ];

    public function alt(string $alt): static
    {
        return $this->attribute('alt', $alt);
    }

    public function fetchPriority(?string $fetchPriority): static
    {
        return $this->attribute('fetchpriority', $fetchPriority);
    }

    public function loading(?string $loading): static
    {
        return $this->attribute('loading', $loading);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'img';
    }
}
