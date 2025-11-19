<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use davidhirtz\yii2\skeleton\assets\SelectableAssetBundle;
use davidhirtz\yii2\skeleton\html\Checkbox;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class CheckboxColumn extends Column
{
    public array $checkboxAttributes = [];
    protected bool $multiple = true;
    protected string $name = 'selection[]';

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        if (substr_compare($this->name, '[]', -2, 2)) {
            $this->name .= '[]';
        }

        return $this;
    }

    #[Override]
    protected function getHeaderContent(): string|Stringable
    {
        $this->registerClientScript();

        if ($this->header !== null || !$this->multiple) {
            return parent::getHeaderContent();
        }

        return Checkbox::make()->attribute('data-check-all', "#{$this->grid->getId()}");
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content !== null) {
            return parent::getBodyContent($model, $key, $index);
        }

        return Checkbox::make()
            ->attribute('data-check', $this->multiple ? 'multiple' : 'single')
            ->addAttributes($this->checkboxAttributes)
            ->name($this->name);
    }

    protected function registerClientScript(): void
    {
        Yii::$app->getView()->registerAssetBundle(SelectableAssetBundle::class);
    }
}
