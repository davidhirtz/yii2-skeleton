<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\skeleton\assets\SelectableAssetBundle;
use Override;
use yii\helpers\Html;

class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    public $checkboxOptions = [
        'class' => 'form-check-input',
    ];

    #[Override]
    public function init(): void
    {
        $this->checkboxOptions['data-id'] = 'check';
        parent::init();
    }

    #[Override]
    protected function renderHeaderCellContent(): string
    {
        if ($this->header !== null || !$this->multiple) {
            return parent::renderHeaderCellContent();
        }

        return Html::checkbox('', false, [
            ...$this->checkboxOptions,
            'data-id' => 'check-all',
        ]);
    }

    //    #[Override]
    //    protected function renderDataCellContent($model, $key, $index): string
    //    {
    //        $checkbox = parent::renderDataCellContent($model, $key, $index);
    //        return $this->multiple ? "<selectable-checkbox>$checkbox</selectable-checkbox>" : $checkbox;
    //
    //    }

    #[Override]
    public function registerClientScript(): void
    {
        SelectableAssetBundle::registerModule('#' . $this->grid->getId());
    }
}
