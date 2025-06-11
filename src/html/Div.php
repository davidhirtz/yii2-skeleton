<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Div extends Tag
{
    use TagContentTrait;

    public static function tag(string $html, array $attributes = []): string
    {
        return static::make()
            ->attributes($attributes)
            ->html($html)
            ->render();
    }

    protected function getName(): string
    {
        return 'div';
    }
}
