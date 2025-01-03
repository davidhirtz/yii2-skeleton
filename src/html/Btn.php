<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\traits\AjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\IconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TooltipAttributeTrait;
use Yiisoft\Html\Tag\Base\NormalTag;

final class Btn extends NormalTag
{
    use AjaxAttributeTrait;
    use IconTextTrait;
    use TooltipAttributeTrait;

    protected array $attributes = [
        'type' => 'button',
    ];

    public static function danger(?string $text = null): self
    {
        return self::tag()
            ->class('btn btn-danger')
            ->text($text);
    }

    public static function primary(?string $text = null): self
    {
        return self::tag()
            ->class('btn btn-primary')
            ->text($text);
    }

    public static function secondary(?string $text = null): self
    {
        return self::tag()
            ->class('btn btn-secondary')
            ->text($text);
    }

    public static function success(?string $text = null): self
    {
        return self::tag()
            ->class('btn btn-success')
            ->text($text);
    }

    public static function transparent(?string $text = null): self
    {
        return self::tag()
            ->class('btn-transparent')
            ->text($text);
    }

    public function href(string|array|null $route): self
    {
        if ($route !== null) {
            $new = clone $this;
            $new->attributes['href'] = Url::to($route);
            return $new;
        }

        return $this;
    }

    public function modal(string $id): self
    {
        $new = clone $this;
        $new->attributes['data-modal'] = str_starts_with('#', $id) ? $id : "#$id";
        return $new;
    }

    public function type(?string $type): self
    {
        $new = clone $this;
        $new->attributes['type'] = $type;
        return $new;
    }

    protected function prepareAttributes(): void
    {
        if (!empty($this->attributes['href'])) {
            unset($this->attributes['type']);
        }

        parent::prepareAttributes();
    }

    protected function generateContent(): string
    {
        return $this->generateIconTextContent();
    }

    protected function getName(): string
    {
        return !empty($this->attributes['href']) ? 'a' : 'button';
    }
}
