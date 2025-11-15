<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\FilterDropdown;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

trait StatusGridViewTrait
{
    protected string|false|null $statusDefaultItem = null;
    protected string $statusParamName = 'status';

    protected function getStatusColumn(): DataColumn
    {
        return DataColumn::make()
            ->attribute('status')
            ->header(false)
            ->content($this->getStatusColumnContent(...))
            ->centered();
    }

    protected function getStatusColumnContent(ActiveRecordInterface&StatusAttributeInterface $model): string
    {
        $icon = $this->getStatusIcon($model);
        return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
    }

    public function getStatusDropdown(): FilterDropdown
    {
        return new FilterDropdown(
            $this->statusDropdownItems(),
            Yii::t('skeleton', 'Status'),
            $this->statusParamName,
            $this->statusDefaultItem
        );
    }

    protected function getStatusIcon(StatusAttributeInterface $model): string
    {
        return Html::icon($model->getStatusIcon())
            ->tooltip($model->getStatusName())
            ->render();
    }

    protected function statusDropdownItems(): array
    {
        return array_map(fn ($options) => $options['plural'] ?? $options['name'], $this->getModel()::getStatuses());
    }

    /**
     * @todo Extract this to class
     */
    protected function statusSelectionButtonItems(?StatusAttributeInterface $model = null, array|string|null $url = null): array
    {
        $model ??= $this->getModel();
        $paramName = $model instanceof Model ? Html::getInputName($model, 'status') : $this->statusParamName;
        $items = [];

        foreach ($model::getStatuses() as $status => $statusOptions) {
            $items[] = Button::make()
                ->attribute('hx-include', '[data-id="check"]:checked')
                ->name($paramName)
                ->post($url ?? ['update-all'])
                ->text($statusOptions['name'])
                ->value($status);
        }

        return $items;
    }
}
