<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagModalTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;

class Button extends Tag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
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

    public static function transparent(string $text = ''): static
    {
        return static::make()->class('btn btn-transparent')->text($text);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->attribute('disabled', $disabled);
    }

    public function name(?string $name): static
    {
        return $this->attribute('name', $name);
    }

    public function type(?string $type): static
    {
        return $this->attribute('type', $type);
    }

    public function value(mixed $value): static
    {
        return $this->attribute('value', $value);
    }

    protected function getName(): string
    {
        return isset($this->attributes['href']) ? 'a' : 'button';
    }
}
