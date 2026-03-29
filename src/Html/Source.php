<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\VoidTag;
use Hirtz\Skeleton\Html\Traits\TagImageAttributesTrait;
use Override;

class Source extends VoidTag
{
    use TagImageAttributesTrait;

    public function media(?string $media): static
    {
        return $this->attribute('media', $media);
    }

    public function type(?string $type): self
    {
        return $this->attribute('type', $type);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'source';
    }
}
