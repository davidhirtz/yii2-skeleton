<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Override;

class Video extends Tag
{
    public function autoplay(?bool $autoplay = true): static
    {
        return $this->attribute('autoplay', $autoplay);
    }

    public function controls(?bool $controls = true): static
    {
        return $this->attribute('controls', $controls);
    }

    public function loop(?bool $loop = true): static
    {
        return $this->attribute('loop', $loop);
    }

    public function muted(?bool $muted = true): static
    {
        return $this->attribute('muted', $muted);
    }

    public function playsinline(?bool $playsinline = true): static
    {
        return $this->attribute('playsinline', $playsinline);
    }

    public function preload(?string $preload = null): static
    {
        return $this->attribute('preload', $preload);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'video';
    }
}
