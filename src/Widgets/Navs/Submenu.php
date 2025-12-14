<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
use Hirtz\Skeleton\Html\Traits\TagUrlTrait;
use Hirtz\Skeleton\Widgets\Navs\Traits\NavItemTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
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
