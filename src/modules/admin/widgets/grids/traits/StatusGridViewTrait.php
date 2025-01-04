<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\StatusAttributeInterface;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

trait StatusGridViewTrait
{
    /**
     * @var int|null the current status
     */
    public ?int $status = null;

    /**
     * @var string the status parameter name
     */
    public string $statusParamName = 'status';

    /**
     * @var bool|null set to manually set the status dropdown state
     */
    public ?bool $statusIsActive = null;

    /**
     * @var string|null|false the default status item label, set to `false` to disable the default item
     */
    public string|null|false $statusDefaultItem = null;

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
        $items = $this->statusDropdownItems();
        $label = $items[$this->status]['label'] ?? false;

        return ButtonDropdown::widget([
            'label' => $label ? Html::tag('strong', $label) : Yii::t('skeleton', 'Status'),
            'items' => $items,
            'isActive' => $this->statusIsActive,
            'paramName' => $this->statusParamName,
            'defaultItem' => $this->statusDefaultItem,
        ]);
    }

    protected function getStatusIcon(StatusAttributeInterface $model): string
    {
        return Icon::tag($model->getStatusIcon())
            ->tooltip($model->getStatusName())
            ->render();
    }

    /**
     * Returns array index by `status`, containing `label` and `url` for {@see ButtonDropdown}. This method can be
     * overridden to use other items or add additional statuses to the dropdown.
     */
    protected function statusDropdownItems(): array
    {
        $model = $this->getModel();
        $items = [];

        foreach ($model::getStatuses() as $id => $status) {
            $items[$id] = [
                'label' => $status['plural'] ?? $status['name'],
                'url' => Url::current([$this->statusParamName => $id, 'page' => null]),
            ];
        }

        return $items;
    }

    protected function statusSelectionButtonItems(?StatusAttributeInterface $model = null): array
    {
        $model ??= $this->getModel();
        $paramName = $model instanceof Model ? Html::getInputName($model, 'status') : $this->statusParamName;
        $items = [];

        foreach ($model::getStatuses() as $status => $statusOptions) {
            $items[] = [
                'label' => $statusOptions['name'],
                'url' => '#',
                'linkOptions' => [
                    'data-method' => 'post',
                    'data-form' => $this->getSelectionFormId(),
                    'data-params' => [$paramName => $status],
                ],
            ];
        }

        return $items;
    }
}
