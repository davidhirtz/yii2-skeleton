<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\H2;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use yii\base\Model;

/**
 * The header of a page that belongs to one record. The H1 stays on the **base** record — the one that owns the
 * page — and every record between it and the page's own becomes the subtitle beneath, so an asset four levels
 * down still reads "About — Section #3 · Asset #1 · Hotspot #2 · Asset #1". Which record is the base is
 * {@see AdminModelInterface::getAdminSubtitle()}: a record that answers `null` owns its page, which is why an
 * entry filed under another entry is still its own base. No header knows another header's class, and the
 * complete map stays the breadcrumb bar's job.
 *
 * @template TModel of (Model&AdminModelInterface)|null = (Model&AdminModelInterface)|null
 */
class ModelHeader extends Header
{
    /**
     * @use ModelTrait<TModel>
     */
    use ModelTrait;

    /**
     * @var list<Stringable> the chain below the base record, each item linked where it has a page of its own
     */
    protected array $subtitleItems = [];

    /**
     * None of the shipped models can form a cycle, but `getAdminParent()` is a project extension point.
     */
    protected int $maxChainCount = 16;

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            [$base, $chain] = $this->getModelChain($this->model);

            $this->title ??= $base->getAdminName();
            $this->url ??= $base->getAdminRoute() ?: null;

            if ($this->subtitle === null) {
                $this->subtitleItems = $this->getSubtitleItems($chain);
            }

            $this->addBreadcrumbs($this->getModelBreadcrumbs());
        }

        parent::configure();
    }

    /**
     * @return array{AdminModelInterface, list<AdminModelInterface>} the base record, and everything between it
     *     and `$model` with `$model` itself last
     */
    protected function getModelChain(AdminModelInterface $model): array
    {
        $chain = [];

        while ($model->getAdminSubtitle() !== null && count($chain) < $this->maxChainCount) {
            $chain[] = $model;
            $parent = $model->getAdminParent();

            if (!$parent) {
                break;
            }

            $model = $parent;
        }

        return [$model, array_reverse($chain)];
    }

    /**
     * The subtitle is markup rather than a joined string: each record links to its own page where it has one,
     * and the separator between them is the `.header-subtitle-item` rule rather than a character.
     *
     * @param list<AdminModelInterface> $chain
     * @return list<Stringable>
     */
    protected function getSubtitleItems(array $chain): array
    {
        $items = [];

        foreach ($chain as $model) {
            $subtitle = $model->getAdminSubtitle();

            if ($subtitle === null) {
                continue;
            }

            $route = $model->getAdminRoute();

            $items[] = ($route ? A::make()->href($route) : Span::make())
                ->class('header-subtitle-item')
                ->text($subtitle);
        }

        return $items;
    }

    #[Override]
    protected function getSubtitle(): ?Stringable
    {
        return $this->subtitleItems
            ? H2::make()
                ->class('header-subtitle')
                ->content(...$this->subtitleItems)
            : parent::getSubtitle();
    }

    /**
     * @return list<AdminModelInterface>
     */
    protected function getAdminAncestors(): array
    {
        $ancestors = [];
        $model = $this->model;

        while (($model = $model?->getAdminParent()) && count($ancestors) < $this->maxChainCount) {
            $ancestors[] = $model;
        }

        return array_reverse($ancestors);
    }

    /**
     * @return list<Breadcrumb>
     */
    protected function getModelBreadcrumbs(): array
    {
        $breadcrumbs = [];

        foreach ($this->getAdminAncestors() as $ancestor) {
            $breadcrumbs[] = $ancestor->getAdminIndexBreadcrumb();
            $breadcrumbs[] = new Breadcrumb($ancestor->getAdminName(), $ancestor->getAdminRoute() ?: null);
        }

        $breadcrumbs[] = $this->model?->getAdminIndexBreadcrumb();

        return array_values(array_filter($breadcrumbs));
    }
}
