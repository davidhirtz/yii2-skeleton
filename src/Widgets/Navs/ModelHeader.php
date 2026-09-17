<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Ol;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use yii\base\Model;

/**
 * The header of a page that belongs to one record: it names that record and walks
 * {@see AdminModelInterface::getAdminParent()} for everything above it, so no header knows another one. A subclass
 * that leaves `title` set keeps its own title but still gets the path and the breadcrumbs.
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
     * A chain longer than this collapses into a non-linked `…`; the breadcrumb bar scrolls and is never capped.
     */
    protected int $maxPathCount = 3;

    /**
     * None of the shipped models can form a cycle, but `getAdminParent()` is a project extension point.
     */
    protected int $maxChainCount = 16;

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            $this->title ??= $this->model->getAdminName();
            $this->url ??= $this->model->getAdminRoute() ?: null;

            if ($this->model instanceof TypeAttributeInterface) {
                $this->subtitle ??= $this->model->getAdminType();
            }

            $this->addBreadcrumbs($this->getModelBreadcrumbs());
        }

        parent::configure();
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

    #[Override]
    protected function getHeaderPath(): ?Stringable
    {
        $ancestors = $this->getAdminAncestors();

        if (!$ancestors) {
            return null;
        }

        $path = Ol::make()->class('header-path small');

        if (count($ancestors) > $this->maxPathCount) {
            $ancestors = array_slice($ancestors, -$this->maxPathCount);
            $path->addContent(Li::make()->class('header-path-item')->text('…'));
        }

        foreach ($ancestors as $ancestor) {
            $path->addContent(Li::make()
                ->class('header-path-item')
                ->content($this->getPathItemContent($ancestor)));
        }

        return $path;
    }

    protected function getPathItemContent(AdminModelInterface $model): Stringable
    {
        $route = $model->getAdminRoute();
        $tag = $route ? A::make()->href($route) : Span::make();
        $icon = $model->getAdminIcon();

        return $tag
            ->class('header-path-link')
            ->content($icon ? Icon::make()->name($icon)->addClass('header-path-icon') : null)
            ->addText($model->getAdminName());
    }
}
