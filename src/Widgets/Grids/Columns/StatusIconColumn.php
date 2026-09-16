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
     * The closure a caller hands `enableUpdate()` is typed against whichever model it re-binds the column to, which
     * the column itself has no way of holding — it applies it to the model the grid gives it.
     *
     * @var Closure(mixed): bool|bool
     */
    protected Closure|bool $enableUpdate = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->format ??= 'raw';
        $this->property ??= 'status';
        $this->title ??= false;
        $this->value ??= $this->getStatusIcon(...);

        parent::__construct($config);
    }

    /**
     * Whether a click cycles the record through its statuses. The grid answers, since only it knows whether it is a
     * picker and which permission its records need — and a grid whose rows are of mixed classes, as the media
     * `FileAssetGridView` is, answers per record with a closure.
     *
     * Keeps the {@see StatusIconColumn} type when the model type is re-bound, the way
     * {@see LinkColumn::url()} does.
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
            ->tooltip(Yii::t('skeleton', 'COMMON_STATUS_BUTTON_TOOLTIP', [
                'status' => $model->getStatusName(),
                'next' => $next->getName(),
            ]))
            ->ariaLabel($next->getName())
            // The grid alone, so a status toggle leaves the user where they are.
            ->replace($url, "#{$this->grid->getId()}");
    }

    /**
     * The status action sits beside the update action the record's own admin route names — only the model knows its
     * controller, so `update` is swapped for `status` rather than the route rebuilt. Anything else keeps the plain
     * icon: a model with no admin page of its own answers `false`, and one whose route falls back to the index
     * (`Models\User`, the cms `Category`, the media `Folder`) carries no primary key to act on.
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
