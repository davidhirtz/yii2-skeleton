<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\VoidTag;
use Override;

class Track extends VoidTag
{
    public function default(?bool $default = true): static
    {
        return $this->attribute('default', $default);
    }

    public function kind(?string $kind): static
    {
        return $this->attribute('kind', $kind);
    }

    public function label(?string $label): static
    {
        return $this->attribute('label', $label);
    }

    public function src(?string $src): static
    {
        return $this->attribute('src', $src);
    }

    public function srclang(?string $srclang): static
    {
        return $this->attribute('srclang', $srclang);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'track';
    }
}
