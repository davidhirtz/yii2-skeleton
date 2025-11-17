<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use Override;

class Img extends Tag
{
    use TagContentTrait;

    private ?string $src = null;

    public function alt(string $alt): static
    {
        return $this->attribute('alt', $alt);
    }

    public function src(string|array|null $src): static
    {
        $this->src = $src !== null ? Url::to($src) : null;
        return $this;
    }

    #[Override]
    protected function before(): string
    {
        $this->attributes['alt'] ??= '';
        $this->attributes['src'] ??= $this->src;

        return parent::before();
    }

    #[Override]
    protected function getTag(): string
    {
        return '<' . $this->getTagName() . $this->getAttributes() . '>';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'img';
    }
}
