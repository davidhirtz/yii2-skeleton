<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\navs;

use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Div;
use Hirtz\Skeleton\html\H1;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagTitleTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;

class Header extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagTitleTrait;
    use TagUrlTrait;

    protected function renderContent(): string
    {
        return $this->getTitle() . $this->getContent();
    }

    protected function getTitle(): Stringable
    {
        $content = $this->url
            ? A::make()
                ->text($this->title)
                ->href($this->url)
            : $this->title;

        return H1::make()
            ->attributes($this->attributes)
            ->addClass('page-header')
            ->content($content);
    }

    protected function getContent(): ?Stringable
    {
        return $this->content
            ? Div::make()
                ->class('small')
                ->content(...$this->content)
            : null;
    }
}
