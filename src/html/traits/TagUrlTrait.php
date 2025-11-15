<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use yii\helpers\Url;

trait TagUrlTrait
{
    protected array|string|null $url = null;

    public function url(array|string|null $url): static
    {
        $this->url = $url ? Url::to($this->url) : $url;
        return $this;
    }
}