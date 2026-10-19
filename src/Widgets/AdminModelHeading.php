<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Models\AdminModelChain;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Override;
use Stringable;

/**
 * A record named as the search names it, "About — Section 2/5": a section's own name says nothing about where it is.
 */
class AdminModelHeading extends Widget
{
    use TagAttributesTrait;

    protected ?AdminModelInterface $model = null;

    public function model(?AdminModelInterface $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if ($this->model === null) {
            return '';
        }

        $chain = AdminModelChain::fromModel($this->model);

        $title = Span::make()
            ->class('model-heading-title')
            ->text($chain->base->getAdminName());

        $subtitles = array_map(static fn (string $subtitle): Stringable => Span::make()
            ->class('model-heading-subtitle')
            ->text($subtitle), $chain->subtitles);

        return Span::make()
            ->attributes($this->attributes)
            ->addClass('model-heading')
            ->content($title, ...$subtitles);
    }
}
