<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\navs;

use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagTitleTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\widgets\navs\traits\NavItemTrait;
use Hirtz\Skeleton\widgets\traits\ContainerWidgetTrait;
use Hirtz\Skeleton\widgets\Widget;
use Override;
use Stringable;

class Submenu extends Widget
{
    use ContainerWidgetTrait;
    use TagContentTrait;
    use TagTitleTrait;
    use TagUrlTrait;
    use NavItemTrait;

    public array $navAttributes = ['class' => 'submenu nav-pills'];
    public array $headerAttributes = [];

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getHeader() . $this->getNav();
    }

    protected function getHeader(): ?Header
    {
        return $this->title
            ? Header::make()
                ->attributes($this->headerAttributes)
                ->title($this->title)
                ->url($this->url)
            : null;
    }

    protected function getNav(): Nav
    {
        return Nav::make()
                ->attributes($this->navAttributes)
                ->items(...$this->items);
    }
}
