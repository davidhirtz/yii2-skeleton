<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;

class Script extends Base\Tag
{
    use TagContentTrait;

    public function async(bool $async = true): self
    {
        $this->attributes['async'] = $async;
        return $this;
    }

    public function charset(?string $charset): self
    {
        $this->attributes['charset'] = $charset;
        return $this;
    }

    public function defer(bool $defer = true): self
    {
        $this->attributes['defer'] = $defer;
        return $this;
    }

    public function nonce(?string $nonce): self
    {
        if ($nonce === null) {
            unset($this->attributes['nonce']);
        } else {
            $this->attributes['nonce'] = $nonce;
        }

        return $this;
    }

    public function src(?string $url): self
    {
        $this->attributes['src'] = $url;
        return $this;
    }

    public function type(?string $type): self
    {
        $this->attributes['type'] = $type;
        return $this;
    }

    protected function getTagName(): string
    {
        return 'script';
    }
}
