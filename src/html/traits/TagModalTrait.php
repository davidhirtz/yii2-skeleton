<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\widgets\Modal;

trait TagModalTrait
{
    private ?Modal $modal = null;

    public function modal(Modal $modal): static
    {
        $this->modal = $modal;
        return $this->type('button');
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
