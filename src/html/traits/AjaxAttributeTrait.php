<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Url;

trait AjaxAttributeTrait
{
    public function confirm(?string $message): self
    {
        // todo: implement
        return $this;
    }

    public function get(string|array $url): self
    {
        $new = clone $this;
        $new->attributes['hx-get'] = Url::to($url);
        return $new;
    }

    public function post(string|array $url): self
    {
        $new = clone $this;

        $new->attributes['hx-post'] = Url::to($url);
        $new->attributes['hx-swap'] ??= 'show:top';
        $new->attributes['hx-target'] ??= 'body';

        return $new;
    }

    public function swap(string $swap): self
    {
        $new = clone $this;
        $new->attributes['hx-swap'] = $swap;
        return $new;
    }

    public function target(string $target): self
    {
        $new = clone $this;
        $new->attributes['hx-target'] = $target;
        return $new;
    }
}
