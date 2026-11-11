<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Icon;
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

    protected bool $wide = false;

    /**
     * Two columns from the `sm` breakpoint, where the grid has two.
     */
    public function wide(bool $wide = true): static
    {
        $this->wide = $wide;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $body = (string)$this->getBody();

        if ($body === '') {
            return '';
        }

        return Card::make()
            ->title($this->getTitle())
            ->addClass('dashboard-item', $this->wide ? 'dashboard-item-wide' : null)
            ->content($body);
    }

    protected function getBody(): string|Stringable
    {
        return implode('', $this->content);
    }

    /**
     * The link is the row, as a nav link is, so the whole row is the hover and the target.
     *
     * @param array<int|string, mixed>|string|null $url
     */
    protected function getListItem(string|Stringable $content, array|string|null $url = null): Li
    {
        $item = $url !== null
            ? A::make()->class('dashboard-list-item', 'dashboard-list-link')->href($url)
            : Div::make()->class('dashboard-list-item');

        return Li::make()->content($item->content($content));
    }

    protected function getListRow(?string $icon, string|Stringable ...$content): Div
    {
        return Div::make()
            ->class('dashboard-list-row')
            ->content($icon ? Icon::make()->name($icon)->addClass('dashboard-list-icon') : null, ...$content);
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
