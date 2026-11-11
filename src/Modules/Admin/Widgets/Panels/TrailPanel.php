<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Stringable;
use Yii;

abstract class TrailPanel extends DashboardPanel
{
    protected int $limit = 5;

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return list<string>
     */
    protected function getModelClasses(): array
    {
        $module = Yii::$app->getModule('admin');
        return $module instanceof Module ? $module->dashboardTrailModels : [];
    }

    /**
     * @param list<string> $classes
     */
    protected function isListedClass(string $class, array $classes): bool
    {
        foreach ($classes as $listed) {
            if (is_a($class, $listed, true)) {
                return true;
            }
        }

        return false;
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

    /**
     * @return array<int|string, mixed>|null
     */
    protected function getRecordUrl(AdminModelInterface $record): ?array
    {
        $route = $record->getAdminRoute();
        return $route && $this->webuser->can($record->getPermissionName()) ? $route : null;
    }

    /**
     * @param list<string|Stringable> $items
     */
    protected function getList(array $items): string|Stringable
    {
        return $items ? Ul::make()->class('dashboard-list')->content(...$items) : '';
    }

    /**
     * @param list<string|Stringable|null> $meta
     * @param array<int|string, mixed>|null $url
     */
    protected function getLine(string|Stringable $title, array $meta, Trail $trail, ?array $url = null): Stringable
    {
        return $this->getListItem($this->getListRow(
            $trail->getType()?->getIcon(),
            Div::make()->content(
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
            ),
        ), $url);
    }
}
