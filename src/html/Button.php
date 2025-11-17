<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagModalTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
use Override;

class Button extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagInputTrait;
    use TagLinkTrait;
    use TagModalTrait;
    use TagTooltipAttributeTrait;
    
    public array $attributes = [
        'type' => 'button',
    ];

    public function danger(): static
    {
        return $this->addClass('btn btn-danger');
    }

    public function primary(): static
    {
        return $this->addClass('btn btn-primary');
    }

    public function success(): static
    {
        return $this->addClass('btn btn-success');
    }

    public function secondary(): static
    {
        return $this->addClass('btn btn-secondary');
    }

    public function link(): static
    {
        return $this->addClass('btn btn-link');
    }

    #[Override]
    protected function getTagName(): string
    {
        return isset($this->attributes['href']) ? 'a' : 'button';
    }
}
