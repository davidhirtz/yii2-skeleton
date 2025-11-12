<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use davidhirtz\yii2\skeleton\assets\SelectableAssetBundle;
use davidhirtz\yii2\skeleton\html\Checkbox;
use Override;
use yii\grid\Column;

class CheckboxColumn extends Column
{
    public array $checkboxAttributes = [];
    public bool $multiple = true;
    public string $name = 'selection';

    #[Override]
    public function init(): void
    {
        if ($this->multiple && substr_compare($this->name, '[]', -2, 2)) {
            $this->name .= '[]';
        }

        $this->registerClientScript();

        parent::init();
    }

    #[Override]
    protected function renderHeaderCellContent(): string
    {
        if ($this->header !== null || !$this->multiple) {
            return parent::renderHeaderCellContent();
        }

        return Checkbox::make()
            ->attribute('data-check-all', "#{$this->grid->getId()}")
            ->render();
    }

    #[Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        if ($this->content !== null) {
            return parent::renderDataCellContent($model, $key, $index);
        }

        return Checkbox::make()
            ->attribute('data-check', $this->multiple ? 'multiple' : 'single')
            ->addAttributes($this->checkboxAttributes)
            ->name($this->name)
            ->render();
    }

    public function registerClientScript(): void
    {
        $this->grid->getView()->registerAssetBundle(SelectableAssetBundle::class);
    }
}
