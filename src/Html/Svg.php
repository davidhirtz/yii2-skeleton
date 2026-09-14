<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html;

use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Override;

class Svg extends Tag
{
    use TagContentTrait;

    public const string XML_NAMESPACE = 'http://www.w3.org/2000/svg';

    protected bool $showEmpty = false;

    public function fill(?string $fill): static
    {
        return $this->attribute('fill', $fill);
    }

    public function height(int|string|null $height): static
    {
        return $this->attribute('height', $height);
    }

    public function viewBox(?string $viewBox): static
    {
        return $this->attribute('viewBox', $viewBox);
    }

    public function width(int|string|null $width): static
    {
        return $this->attribute('width', $width);
    }

    /**
     * Inline SVG inherits the namespace from the HTML document, a standalone document has to declare it.
     */
    public function xmlns(?string $xmlns = self::XML_NAMESPACE): static
    {
        return $this->attribute('xmlns', $xmlns);
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'svg';
    }
}
