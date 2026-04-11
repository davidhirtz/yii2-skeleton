<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\LinkTrait;
use Hirtz\Skeleton\Widgets\Traits\OrderTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class DashboardItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use IconTrait;
    use LabelTrait;
    use LinkTrait;
    use OrderTrait;
    use UrlTrait;
    use VisibilityTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->isVisible()) {
            return '';
        }


        return Li::make()
            ->attributes($this->attributes)
            ->addClass('dashboard-item')
            ->content($this->getContent());
    }

    protected function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->class('dashboard-link')
            ->href($this->url);

        $link->addContent($this->getIcon()?->addClass('dashboard-link-icon'));

        if ($this->label) {
            $link->addContent(Span::make()->text($this->label));
        }

        return $this->getLink($link);
    }
}
