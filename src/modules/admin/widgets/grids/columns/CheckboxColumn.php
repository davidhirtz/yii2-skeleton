<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\skeleton\assets\SelectableAssetBundle;
use yii\helpers\Html;

class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    public $checkboxOptions = [
        'class' => 'form-check-input',
    ];

    public function init(): void
    {
        $this->checkboxOptions['data-id'] = 'check';
        parent::init();
    }

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

    public function registerClientScript(): void
    {
        SelectableAssetBundle::registerModule('#' . $this->grid->getId());
    }
}
