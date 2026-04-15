<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Widgets\Traits\FormatTrait;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Override;
use Stringable;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

class DataColumn extends Column
{
    use FormatTrait;
    use PropertyTrait;

    protected ?string $label = null;
    protected ?Closure $value = null;
    protected array $sortLinkAttributes = [];
    protected bool $enableSorting = true;
    protected bool $encodeLabel = true;

    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function value(?Closure $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function enableSorting(bool $enableSorting): static
    {
        $this->enableSorting = $enableSorting;
        return $this;
    }

    public function sortLinkAttributes(array $attributes): static
    {
        $this->sortLinkAttributes = $attributes;
        return $this;
    }

    #[Override]
    protected function getHeaderContent(): string|Stringable
    {
        if ($this->header !== null || ($this->label === null && $this->property === null)) {
            return parent::getHeaderContent();
        }

        $label = $this->label
            ?? current($this->grid->provider->getModels())?->getAttributeLabel($this->property)
            ?: Inflector::camel2words($this->property);

        if ($this->encodeLabel) {
            $label = Html::encode($label);
        }

        if (
            $this->property !== null
            && $this->enableSorting
            && ($sort = $this->grid->provider->getSort()) !== false && $sort->hasAttribute($this->property)
        ) {
            return $sort->link($this->property, [
                ...$this->sortLinkAttributes,
                'label' => $label,
            ]);
        }

        return $label;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        return $this->content === null
            ? $this->formatValue($this->getValue($model, $key, $index))
            : parent::getBodyContent($model, $key, $index);
    }

    protected function getValue(array|Model $model, string|int $key, int $index): mixed
    {
        return $this->value instanceof Closure
            ? ($this->value)($model, $key, $index, $this)
            : ArrayHelper::getValue($model, $this->property);
    }
}
