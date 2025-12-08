<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\custom;

use Hirtz\Skeleton\helpers\Url;
use Hirtz\Skeleton\html\base\Tag;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Override;

class FileUpload extends Tag
{
    use TagContentTrait;

    public function url(string|array|null $url): static
    {
        return $this->attribute('data-url', null !== $url ? Url::to($url) : null);
    }

    public function target(?string $target): static
    {
        return $this->attribute('data-target', $target);
    }

    public function chunkSize(?int $size): static
    {
        return $this->attribute('data-chunk-size', $size);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'file-upload';
    }
}
