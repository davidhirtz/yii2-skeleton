<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Stringable;

abstract class TrailPanel extends DashboardPanel
{
    protected int $limit = 8;

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * The record a row names, `null` once it is gone; a class stored with its language (`Entry::de`) is the class.
     */
    protected function getTrailRecord(Trail $trail): ?AdminModelInterface
    {
        $record = $trail->getModelRecord();

        return $record instanceof AdminModelInterface && $record instanceof ActiveRecord && !$record->getIsNewRecord()
            ? $record
            : null;
    }

    protected function getTrailModelClass(Trail $trail): string
    {
        return explode('::', (string)$trail->model_class)[0];
    }

    protected function getTrailModelKey(Trail $trail): string
    {
        $modelId = is_array($trail->model_id) ? implode('-', $trail->model_id) : (string)$trail->model_id;
        return $this->getTrailModelClass($trail) . '#' . $modelId;
    }

    /**
     * @param Trail[] $trails
     */
    protected function preloadTrailRecords(array $trails): void
    {
        TrailModelCollection::preload($trails);
    }

    protected function getRecordLink(AdminModelInterface $record): string|Stringable
    {
        $route = $record->getAdminRoute();
        $name = $record->getAdminName();

        return $route && $this->webuser->can($record->getPermissionName())
            ? A::make()->href($route)->text($name)
            : Span::make()->text($name);
    }

    /**
     * @param list<string|Stringable> $lines
     */
    protected function getList(array $lines): string|Stringable
    {
        if (!$lines) {
            return '';
        }

        $list = Ul::make()->class('dashboard-list');

        foreach ($lines as $line) {
            $list->addContent(Li::make()->content($line));
        }

        return $list;
    }

    /**
     * @param list<string|Stringable|null> $meta
     */
    protected function getLine(string|Stringable $title, array $meta, Trail $trail): Stringable
    {
        return Div::make()
            ->content(
                Div::make()
                    ->class('dashboard-list-title')
                    ->content($title),
                Div::make()
                    ->class('dashboard-list-meta small text-muted')
                    ->content(...[
                        ...array_map(
                            fn (string|Stringable $item): Stringable => Span::make()->content($item),
                            array_values(array_filter($meta, fn ($item) => $item !== null && (string)$item !== '')),
                        ),
                        Span::make()->content(RelativeTime::make()->value($trail->created_at)),
                    ]),
            );
    }
}
