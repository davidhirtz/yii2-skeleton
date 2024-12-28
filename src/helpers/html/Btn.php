<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers\html;

use davidhirtz\yii2\skeleton\helpers\html\traits\AjaxAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\html\traits\IconTextTrait;
use davidhirtz\yii2\skeleton\helpers\html\traits\TooltipAttributeTrait;
use davidhirtz\yii2\skeleton\helpers\Url;
use Yiisoft\Html\Tag\Base\NormalTag;

final class Btn extends NormalTag
{
    use AjaxAttributeTrait;
    use IconTextTrait;
    use TooltipAttributeTrait;

    public static function primary(?string $text = null): self
    {
        return self::tag()->addClass('btn btn-primary')->text($text);
    }

    public static function secondary(?string $text = null): self
    {
        return self::tag()->addClass('btn btn-secondary')->text($text);
    }

    public static function success(?string $text = null): self
    {
        return self::tag()->addClass('btn btn-success')->text($text);
    }

    public static function danger(?string $text = null): self
    {
        return self::tag()->addClass('btn btn-danger')->text($text);
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

    protected function generateContent(): string
    {
        return $this->generateIconTextContent();
    }

    protected function getName(): string
    {
        return !empty($this->attributes['href']) ? 'a' : 'button';
    }
}
