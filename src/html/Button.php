<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagModalTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;

class Button extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagInputTrait;
    use TagLinkTrait;
    use TagModalTrait;
    use TagTooltipAttributeTrait;
    
    protected array $attributes = [
        'type' => 'button',
    ];

    public static function danger(string $text = ''): static
    {
        return static::make()->class('btn btn-danger')->text($text);
    }

    public static function primary(string $text = ''): static
    {
        return static::make()->class('btn btn-primary')->text($text);
    }

    public static function secondary(string $text = ''): static
    {
        return static::make()->class('btn btn-secondary')->text($text);
    }

    public static function success(string $text = ''): static
    {
        return static::make()->class('btn btn-success')->text($text);
    }

    public static function link(string $text = ''): static
    {
        return static::make()->class('btn btn-link')->text($text);
    }

    #[\Override]
    protected function getName(): string
    {
        return isset($this->attributes['href']) ? 'a' : 'button';
    }
}
