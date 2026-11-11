<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Helpers\Url;
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
    protected bool $lazy = false;
    protected ?string $name = null;

    /**
     * Renders a placeholder that loads the panel after the page, by the name {@see Dashboard} gives it.
     */
    public function lazy(bool $lazy = true): static
    {
        $this->lazy = $lazy;
        return $this;
    }

    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

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
        if ($this->lazy && $this->name !== null) {
            return $this->getCard()
                ->addAttributes([
                    'aria-busy' => 'true',
                    'hx-get' => Url::toRoute(['/admin/dashboard/panel', 'name' => $this->name]),
                    'hx-select' => '',
                    'hx-select-oob' => '',
                    'hx-swap' => 'outerHTML',
                    'hx-target' => 'this',
                    'hx-trigger' => 'load',
                ])
                ->content(Div::make()->class('dashboard-item-placeholder'));
        }

        $body = (string)$this->getBody();

        return $body !== '' ? $this->getCard()->content($body) : '';
    }

    protected function getCard(): Card
    {
        return Card::make()
            ->title($this->getTitle())
            ->addClass('dashboard-item', $this->wide ? 'dashboard-item-wide' : null);
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
