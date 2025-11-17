<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\FilterDropdown;
use Stringable;
use Yii;
use yii\base\Model;

trait StatusGridViewTrait
{
    protected string|false|null $statusDefaultItem = null;
    protected string $statusParamName = 'status';

    protected function getStatusColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('status')
            ->header(false)
            ->content($this->getStatusIcon(...))
            ->href(fn ($model) => $this->getRoute($model))
            ->centered();
    }

    protected function getStatusIcon(StatusAttributeInterface $model): Stringable
    {
        return Icon::make()
            ->name($model->getStatusIcon())
            ->tooltip($model->getStatusName());
    }

    public function getStatusDropdown(): FilterDropdown
    {
        return FilterDropdown::make()
            ->label(Yii::t('skeleton', 'Status'))
            ->items($this->getStatusDropdownItems())
            ->param($this->statusParamName)
            ->default($this->statusDefaultItem);
    }

    protected function getStatusDropdownItems(): array
    {
        return array_map(fn ($options) => $options['plural'] ?? $options['name'], $this->getModel()::getStatuses());
    }

    /**
     * @todo Extract this to class
     */
    protected function getStatusSelectionButtonItems(?StatusAttributeInterface $model = null, array|string|null $url = null): array
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
