<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagAjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLinkTrait;
use davidhirtz\yii2\skeleton\html\traits\TagModalTrait;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
use Yiisoft\Html\Tag\Base\NormalTag;

class Button extends NormalTag
{
    use TagAjaxAttributeTrait;
    use TagIconTextTrait;
    use TagLinkTrait;
    use TagModalTrait;
    use TagTooltipAttributeTrait;

    protected array $attributes = [
        'type' => 'button',
    ];

    public static function danger(string $content = ''): self
    {
        $new = self::tag()->content($content);
        $new->attributes['class'] = 'btn btn-danger';
        return $new;
    }

    public static function primary(string $content = ''): self
    {
        $new = self::tag()->content($content);
        $new->attributes['class'] = 'btn btn-primary';
        return $new;
    }

    public static function secondary(string $content = ''): self
    {
        $new = self::tag()->content($content);
        $new->attributes['class'] = 'btn btn-secondary';
        return $new;
    }

    public static function success(string $content = ''): self
    {
        $new = self::tag()->content($content);
        $new->attributes['class'] = 'btn btn-success';
        return $new;
    }

    public static function transparent(string $content = ''): self
    {
        $new = self::tag()->content($content);
        $new->attributes['class'] = 'btn btn-transparent';
        return $new;
    }

    public function disabled(bool $disabled = true): self
    {
        return $this->attribute('disabled', $disabled);
    }

    public function name(?string $name): self
    {
        return $this->attribute('name', $name);
    }

    public function type(?string $type): self
    {
        return $this->attribute('type', $type);
    }

    public function value(mixed $value): self
    {
        return $this->attribute('value', $value);
    }

    protected function getName(): string
    {
        return isset($this->attributes['href']) ? 'a' : 'button';
    }
}
