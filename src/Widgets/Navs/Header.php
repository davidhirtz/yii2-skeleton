<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\H1;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Header extends Widget
{
    use ContainerTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use UrlTrait;

    protected ?string $title = null;
    protected string|Stringable|null $subheading = null;

    public function title(?string $title): static
    {
        if (is_string($title)) {
            $this->view->title($title);
            $title = Html::encode($title);
        }

        $this->title = $title;
        return $this;
    }

    public function subheading(string|Stringable|null $subheading): static
    {
        $this->subheading = $subheading;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $wrapper = Div::make()
            ->attributes($this->attributes)
            ->addClass('header');

        $wrapper->content(Div::make()
            ->class('header-content')
            ->content($this->getTitle(), $this->getSubheading()));

        $wrapper->addContent(...$this->content);

        return $wrapper;
    }

    protected function getTitle(): ?Stringable
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

    protected function getSubheading(): ?Stringable
    {
        return $this->subheading
            ? Div::make()
                ->class('small')
                ->content($this->subheading)
            : null;
    }
}
