<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\H2;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\AdminModelChain;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;
use yii\base\Model;

/**
 * The H1 stays on the base record, the first whose {@see AdminModelInterface::getAdminSubtitle()} answers `null`;
 * the records in between become the subtitle.
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
     * @var list<Stringable>
     */
    protected array $subtitleItems = [];

    /**
     * @see AdminModelChain::fromModel()
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
     * The base record, then everything between it and `$model`, `$model` last.
     *
     * @return array{AdminModelInterface, list<AdminModelInterface>}
     */
    protected function getModelChain(AdminModelInterface $model): array
    {
        $chain = AdminModelChain::fromModel($model, $this->maxChainCount);
        return [$chain->base, $chain->models];
    }

    /**
     * Markup rather than a joined string: the separator is the `.header-subtitle-item` rule, outside the link.
     *
     * @param list<AdminModelInterface> $chain
     * @return list<Stringable>
     */
    protected function getSubtitleItems(array $chain): array
    {
        $items = [];

        foreach ($chain as $index => $model) {
            $subtitle = $model->getAdminSubtitle();

            if ($subtitle === null) {
                continue;
            }

            $route = $model->getAdminRoute();

            $item = Span::make()
                ->class('header-subtitle-item')
                ->addStyle(['view-transition-name' => $this->getSubtitleItemName($model, $index)]);

            $items[] = $route
                ? $item->content(A::make()->href($route)->text($subtitle))
                : $item->text($subtitle);
        }

        return $items;
    }

    /**
     * Named after the record's route rather than its position, so a view transition leaves a record shown on both pages
     * in place.
     */
    protected function getSubtitleItemName(AdminModelInterface $model, int $index): string
    {
        $route = $model->getAdminRoute();
        $parts = $route ? array_filter($route, is_scalar(...)) : [$index];
        $name = preg_replace('/[^a-z0-9]+/', '-', strtolower(implode('-', $parts))) ?? '';

        return 'subtitle-' . (trim($name, '-') ?: (string)$index);
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
