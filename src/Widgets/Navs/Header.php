<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Traits\BreadcrumbTrait;
use Override;
use yii\data\ActiveDataProvider;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\H1;
use Hirtz\Skeleton\Html\H2;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class Header extends Widget
{
    use BreadcrumbTrait;
    use ContainerTrait;
    use TagAttributesTrait;
    use TagContentTrait;
    use UrlTrait;

    protected string|Stringable|null $subheading = null;
    protected ?string $subtitle = null;
    protected ?string $title = null;

    public function pagination(ActiveDataProvider|int $page): static
    {
        if ($page instanceof ActiveDataProvider) {
            $page->prepare();
            $page = $page->getPagination()->getPage() + 1;
        }

        return $page > 1
            ? $this->subtitle(Yii::t('skeleton', 'Page {page}', ['page' => $page]))
            : $this;
    }

    public function subheading(string|Stringable|null $subheading): static
    {
        $this->subheading = $subheading;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->breadcrumbs) {
            $this->view->addBreadcrumbs($this->breadcrumbs);
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $wrapper = Div::make()
            ->attributes($this->attributes)
            ->addClass('header');

        $wrapper->content(Div::make()
            ->class('header-content')
            ->content($this->getHeaderContent(), $this->getSubheading()));

        if ($this->content) {
            $wrapper->addContent(Div::make()->content(...$this->content));
        }

        return $wrapper;
    }

    public function subtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function title(?string $title): static
    {
        if ($title) {
            $this->view->title($title);
        }

        $this->title = $title;
        return $this;
    }

    protected function getHeaderContent(): string|Stringable|null
    {
        $subtitle = $this->getSubtitle();

        return $subtitle
            ? Div::make()
                ->class('header-title')
                ->content($this->getTitle(), $subtitle)
            : $this->getTitle();
    }

    protected function getTitle(): ?Stringable
    {
        if (!$this->title) {
            return null;
        }

        if ($this->url) {
            return H1::make()->content(A::make()
                ->text($this->title)
                ->href($this->url));
        }

        return H1::make()->text($this->title);
    }

    protected function getSubtitle(): ?Stringable
    {
        return $this->subtitle
            ? H2::make()
                ->class('header-subtitle')
                ->text($this->subtitle)
            : null;
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
