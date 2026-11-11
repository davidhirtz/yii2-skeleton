<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\LinkTrait;
use Hirtz\Skeleton\Widgets\Traits\OrderTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
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

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->content($this->getContent());
    }

    protected function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $row = Div::make()
            ->class('dashboard-list-row')
            ->content(
                $this->getIcon()?->addClass('dashboard-list-icon'),
                $this->label ? Span::make()->text($this->label) : null,
            );

        return $this->getLink(A::make()
            ->class('dashboard-list-item', 'dashboard-list-link')
            ->href($this->url)
            ->content($row));
    }
}
