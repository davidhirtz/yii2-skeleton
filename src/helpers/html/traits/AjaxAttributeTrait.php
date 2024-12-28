<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers\html\traits;

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
        return $new;
    }
}
