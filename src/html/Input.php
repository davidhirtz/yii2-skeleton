<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;

class Input extends Tag
{
    use TagInputTrait;

    protected array $attributes = [
        'class' => 'form-control',
        'type' => 'text',
    ];

    public function placeholder(?string $placeholder): static
    {
        return $this->attribute('placeholder', $placeholder);
    }

    protected function renderTag(): string
    {
        return '<' . $this->getName() . $this->renderAttributes() . '>';
    }

    protected function getName(): string
    {
        return 'input';
    }
}
