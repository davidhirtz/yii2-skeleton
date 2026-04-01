<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\H1;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Header extends Widget
{
    use ContainerTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use TitleTrait;
    use UrlTrait;

    protected function renderContent(): string|Stringable
    {
        $wrapper = Div::make()
            ->attributes($this->attributes)
            ->addClass('header');

        $wrapper->content(Div::make()
            ->class('header-content')
            ->content($this->renderHeadline(), $this->renderSubheading()));

        return $wrapper;
    }

    protected function renderHeadline(): ?Stringable
    {
        if (!$this->title) {
            return null;
        }

        $content = $this->url
            ? A::make()
                ->content($this->title)
                ->href($this->url)
            : $this->title;

        return H1::make()
            ->attributes($this->attributes)
            ->content($content);
    }

    protected function renderSubheading(): ?Stringable
    {
        return $this->content
            ? Div::make()
                ->class('small')
                ->content(...$this->content)
            : null;
    }
}
