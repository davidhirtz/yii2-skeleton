<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\ModelReferenceInterface;
use Hirtz\Skeleton\Widgets\AdminModelHeading;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Stringable;

/**
 * The columns of a {@see ModelReferenceInterface} grid: the owner by its heading, the attributes, the last change. A
 * column title is the reference's attribute label.
 */
trait ModelReferenceGridTrait
{
    /**
     * @return list<Column|null>
     */
    protected function getModelReferenceColumns(): array
    {
        return [
            $this->getModelColumn(),
            $this->getAttributesColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];
    }

    protected function getModelColumn(): ?Column
    {
        return LinkColumn::make()
            ->property('model_class')
            ->value($this->getModelName(...))
            ->format('raw')
            ->url($this->getModelRoute(...));
    }

    protected function getModelName(ActiveRecord&ModelReferenceInterface $reference): string|Stringable
    {
        $model = $reference->getModel();

        return $model instanceof AdminModelInterface
            ? AdminModelHeading::make()->model($model)
            : Html::encode($reference->getMissingModelName());
    }

    protected function getAttributesColumn(): ?Column
    {
        return DataColumn::make()
            ->property('attribute_counts')
            ->value($this->getAttributesColumnValue(...));
    }

    protected function getAttributesColumnValue(ActiveRecord&ModelReferenceInterface $reference): string
    {
        $labels = [];

        foreach ($reference->getAttributeLabelCounts() as $label => $count) {
            $labels[] = $count > 1 ? "$label ({$count}x)" : $label;
        }

        return implode(', ', $labels);
    }

    protected function getUpdatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(ActiveRecord&ModelReferenceInterface $reference): array
    {
        $route = $this->getModelRoute($reference);
        return $route ? [ViewGridButton::make()->url($route)] : [];
    }

    /**
     * Only for an owner the user may open.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getModelRoute(ActiveRecord&ModelReferenceInterface $reference): ?array
    {
        $model = $reference->getModel();

        if (!$model instanceof AdminModelInterface || !$this->webuser->can($model->getPermissionName())) {
            return null;
        }

        return $model->getAdminRoute() ?: null;
    }
}
