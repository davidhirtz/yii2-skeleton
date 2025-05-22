<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Breadcrumb extends Ul
{
    protected array $attributes = [
        'class' => 'breadcrumb',
    ];

    protected array $itemAttributes = [
        'class' => 'breadcrumb-item',
    ];

    public function addItem(string $html, array $attributes = []): static
    {
        return parent::addItem($html, $attributes ?: $this->itemAttributes);
    }
}
