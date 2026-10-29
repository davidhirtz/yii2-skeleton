<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;
use Yii;
use yii\base\Model;

/**
 * @template TModel of StatusAttributeInterface&Model = StatusAttributeInterface&Model
 * @extends LinkColumn<TModel>
 */
class StatusIconColumn extends LinkColumn
{
    /**
     * Typed `mixed`, since the closure is typed against whichever model `enableUpdate()` re-binds the column to.
     *
     * @var Closure(mixed): bool|bool
     */
    protected Closure|bool $enableUpdate = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->headerAttributes = ['class' => 'grid-col-icon'];
        $this->format ??= 'raw';
        $this->property ??= 'status';
        $this->title ??= false;
        $this->value ??= $this->getStatusIcon(...);

        parent::__construct($config);
    }

    /**
     * The grid answers, since only it knows whether it is a picker and which permission its records need; a grid of
     * mixed classes answers per record with a closure.
     *
     * @template TUpdateModel of StatusAttributeInterface&Model = TModel
     * @param Closure(TUpdateModel): bool|bool $enableUpdate
     * @return self<TUpdateModel>
     */
    public function enableUpdate(Closure|bool $enableUpdate): self
    {
        $this->enableUpdate = $enableUpdate;
        return $this;
    }

    /**
     * @param TModel $model
     */
    protected function getStatusIcon(StatusAttributeInterface&Model $model): Stringable
    {
        $enabled = $this->enableUpdate instanceof Closure
            ? ($this->enableUpdate)($model)
            : $this->enableUpdate;

        $next = $enabled && $model->isStatusUpdatable() ? $model->getNextStatus() : null;
        $url = $next ? $this->getStatusUrl($model) : null;

        if (!$url) {
            return Icon::make()
                ->name($model->getStatusIcon())
                ->tooltip($model->getStatusName());
        }

        return Button::make()
            ->class('btn-icon icon')
            ->icon($model->getStatusIcon())
            ->tooltip(Yii::t('skeleton', 'MODEL_STATUS_TOOLTIP', [
                'status' => $model->getStatusName(),
                'next' => $next->getName(),
            ]))
            // The grid alone, so a status toggle leaves the user where they are.
            ->replace($url, "#{$this->grid->getId()}");
    }

    /**
     * Swaps `update` for `status` in the record's own admin route, since only the model knows its controller; a route
     * without a primary key keeps the plain icon.
     *
     * @param TModel $model
     * @return array<array-key, mixed>|null
     */
    protected function getStatusUrl(StatusAttributeInterface&Model $model): ?array
    {
        $route = $model instanceof AdminModelInterface ? $model->getAdminRoute() : false;

        if (!is_array($route) || !is_string($route[0] ?? null) || !str_ends_with($route[0], '/update')) {
            return null;
        }

        $route[0] = substr_replace($route[0], 'status', -strlen('update'));

        return $route;
    }
}
