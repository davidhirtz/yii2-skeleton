<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\traits\AjaxAttributeTrait;
use davidhirtz\yii2\skeleton\html\traits\IconTextTrait;
use davidhirtz\yii2\skeleton\html\traits\TooltipAttributeTrait;
use Yii;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Base\Tag;
use Yiisoft\Html\Tag\Button;

class Btn extends Tag
{
    use AjaxAttributeTrait;
    use IconTextTrait;
    use TooltipAttributeTrait;

    private A|Button $tag;
    private ?Modal $modal = null;

    public static function danger(?string $text = null): self
    {
        return self::create($text, 'btn btn-danger');
    }

    public static function primary(?string $text = null): self
    {
        return self::create($text, 'btn btn-primary');
    }

    public static function secondary(?string $text = null): self
    {
        return self::create($text, 'btn btn-secondary');
    }

    public static function success(?string $text = null): self
    {
        return self::create($text, 'btn btn-success');
    }

    public static function transparent(?string $text = null): self
    {
        return self::create($text, 'btn btn-transparent');
    }

    private static function create(?string $text, string $class): self
    {
        $self = Yii::createObject(self::class);

        $self->tag = Button::tag()->type('button');
        $self->attributes = ['class' => $class];
        $self->text = $text ?? '';

        return $self;
    }

    public function href(string|array|null $route): self
    {
        $this->tag = A::tag()->attributes([
            ...$this->attributes,
            'href' => $route !== null ? Url::to($route) : null,
            'type' => null,
        ]);

        return $this;
    }

    public function modal(Modal $modal): self
    {
        $this->modal = $modal;
        return $this;
    }

    public function type(?string $type): self
    {
        $this->tag = Button::tag()->attributes([
            ...$this->attributes,
            'href' => null,
            'type' => $type,
        ]);

        return $this;
    }

    protected function before(): string
    {
        if ($this->modal) {
            $this->attributes['data-modal'] ??= '#' . $this->modal->getId();
            return $this->modal->render();
        }

        return '';
    }

    protected function renderTag(): string
    {
        return $this->tag
            ->addAttributes($this->attributes)
            ->content($this->generateIconTextContent())
            ->render();
    }

    protected function getName(): string
    {
        return '';
    }
}
