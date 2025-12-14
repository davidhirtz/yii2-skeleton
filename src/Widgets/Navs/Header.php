<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\H1;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
