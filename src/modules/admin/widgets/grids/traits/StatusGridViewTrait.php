<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\FilterDropdown;
use Yii;
use yii\base\Model;

trait StatusGridViewTrait
{
    protected ?int $status = null;
    protected string|false|null $statusDefaultItem = null;
    protected string $statusParamName = 'status';

    public function statusColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function ($model) {
                $icon = $this->getStatusIcon($model);
                return ($route = $this->getRoute($model)) ? Html::a($icon, $route) : $icon;
            }
        ];
    }

    public function statusDropdown(): string
    {
        $dropdown = FilterDropdown::make();
        $dropdown->label = Yii::t('skeleton', 'Status');
        $dropdown->paramName = $this->statusParamName;
        $dropdown->defaultItem = $this->statusDefaultItem;
        $dropdown->items = $this->statusDropdownItems();

        return $dropdown->render();
    }

    protected function getStatusIcon(StatusAttributeInterface $model): string
    {
        return Icon::tag($model->getStatusIcon())
            ->tooltip($model->getStatusName())
            ->render();
    }

    protected function statusDropdownItems(): array
    {
        $model = $this->getModel();
        return array_map(fn ($options) => $options['plural'] ?? $options['name'], $model::getStatuses());
    }

    protected function statusSelectionButtonItems(?StatusAttributeInterface $model = null): array
    {
        $model ??= $this->getModel();
        $paramName = $model instanceof Model ? Html::getInputName($model, 'status') : $this->statusParamName;
        $items = [];

        foreach ($model::getStatuses() as $status => $statusOptions) {
            $items[] = Button::make()
                ->attribute('hx-include', '[data-id="check"]:checked')
                ->name($paramName)
                ->post($this->selectionRoute)
                ->text($statusOptions['name'])
                ->value($status);
        }

        return $items;
    }
}
