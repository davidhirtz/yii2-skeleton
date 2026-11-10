<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\OrderTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

/**
 * A panel whose body is empty renders nothing, so a panel decides its own visibility from its data.
 */
class DashboardPanel extends Widget
{
    use OrderTrait;
    use TagContentTrait;
    use TitleTrait;

    protected ?string $footerLabel = null;

    /**
     * @var array<int|string, mixed>|string|null
     */
    protected array|string|null $footerUrl = null;

    /**
     * @param array<int|string, mixed>|string|null $url
     */
    public function footer(?string $label, array|string|null $url): static
    {
        $this->footerLabel = $label;
        $this->footerUrl = $url;

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
            ->title($this->title)
            ->addClass('dashboard-panel')
            ->content($body, $this->getFooter());
    }

    protected function getBody(): string|Stringable
    {
        return implode('', $this->content);
    }

    protected function getFooter(): ?Stringable
    {
        if ($this->footerLabel === null || $this->footerUrl === null) {
            return null;
        }

        return Div::make()
            ->class('dashboard-panel-footer')
            ->content(A::make()
                ->href($this->footerUrl)
                ->text($this->footerLabel));
    }
}
