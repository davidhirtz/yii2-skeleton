<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Assets\SelectableAssetBundle;
use Hirtz\Skeleton\Html\Checkbox;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class CheckboxColumn extends Column
{
    public array $checkboxAttributes = ['class' => 'input checkbox'];
    protected bool $multiple = true;
    protected string $param = 'selection[]';

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function param(string $param): static
    {
        $this->param = $param;

        if (substr_compare($this->param, '[]', -2, 2)) {
            $this->param .= '[]';
        }

        return $this;
    }

    #[Override]
    protected function getHeader(): string|Stringable
    {
        $this->registerClientScript();

        if ($this->title !== null || !$this->multiple) {
            return parent::getHeader();
        }

        return Checkbox::make()
            ->attributes($this->checkboxAttributes)
            ->attribute('data-check-all', "#{$this->grid->getId()}");
    }

    #[Override]
    protected function getBody(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return Checkbox::make()
            ->attributes($this->checkboxAttributes)
            ->attribute('data-check', $this->multiple ? 'multiple' : 'single')
            ->name($this->title);
    }

    protected function registerClientScript(): void
    {
        Yii::$app->getView()->registerAssetBundle(SelectableAssetBundle::class);
    }
}
