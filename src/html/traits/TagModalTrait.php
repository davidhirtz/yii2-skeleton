<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\html\Modal;

trait TagModalTrait
{
    private ?Modal $modal = null;

    public function modal(Modal $modal): self
    {
        $new = clone $this;
        $new->modal = $modal;

        return $new;
    }

    protected function before(): string
    {
        if ($this->modal) {
            $this->attributes['data-modal'] ??= '#' . $this->modal->getId();
            return $this->modal->render();
        }

        return '';
    }
}
