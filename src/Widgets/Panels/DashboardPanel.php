<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\OrderTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * A panel whose body is empty renders nothing, so a panel decides its own visibility from its data.
 */
class DashboardPanel extends Widget
{
    use IconTrait;
    use OrderTrait;
    use TagContentTrait;
    use TitleTrait;
    use UrlTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $body = (string)$this->getBody();

        if ($body === '') {
            return '';
        }

        return Card::make()
            ->title($this->getTitle())
            ->addClass('dashboard-item')
            ->content($body);
    }

    protected function getBody(): string|Stringable
    {
        return implode('', $this->content);
    }

    protected function getTitle(): ?Stringable
    {
        if ($this->title === null || $this->title === false || (string)$this->title === '') {
            return null;
        }

        $content = [
            $this->getIcon()?->addClass('dashboard-item-icon'),
            Span::make()->text($this->title),
        ];

        return $this->url !== null
            ? A::make()->class('dashboard-item-title')->href($this->url)->content(...$content)
            : Span::make()->class('dashboard-item-title')->content(...$content);
    }
}
