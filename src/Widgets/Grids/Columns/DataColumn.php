<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Traits\FormatTrait;
use Hirtz\Skeleton\Widgets\Traits\PropertyTrait;
use Override;
use Stringable;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class DataColumn extends Column
{
    use FormatTrait;
    use PropertyTrait;

    protected ?Closure $value = null;
    protected bool $enableSorting = true;

    private ?array $sortCallbacks = null;

    public function __construct(array $config = [])
    {
        $this->content ??= $this->getValue(...);
        parent::__construct($config);
    }

    /**
     * @param Closure(A|Div):(string|Stringable) $closure
     */
    public function sort(Closure $closure): static
    {
        $this->sortCallbacks[] = $closure;
        return $this;
    }

    public function enableSorting(bool $enableSorting): static
    {
        $this->enableSorting = $enableSorting;
        return $this;
    }

    public function value(?Closure $value): static
    {
        $this->value = $value;
        return $this;
    }

    #[Override]
    protected function getHeader(): string|Stringable
    {
        return $this->property !== null && $this->title !== false
            ? $this->evaluate($this->sortCallbacks, $this->getSort())
            : parent::getHeader();
    }

    protected function getSort(): A|Div|null
    {
        $title = $this->title
            ?? current($this->grid->provider->getModels())?->getAttributeLabel($this->property)
            ?: Inflector::camel2words($this->property);


        $sort = $this->enableSorting ? $this->grid->provider->getSort() ?: null : null;

        if (!$sort?->hasAttribute($this->property)) {
            return Div::make()->text($title);
        }

        $direction = $sort->getAttributeOrder($this->property);

        $link = A::make()
            ->href($sort->createUrl($this->property))
            ->text($title);

        return $direction !== null ? $link->class($direction === SORT_ASC ? 'asc' : 'desc') : $link;
    }

    protected function getValue(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $value = $this->value instanceof Closure
            ? ($this->value)($model, $key, $index, $this)
            : $this->getPropertyValue($model);

        return $this->formatValue($value);
    }

    protected function getPropertyValue(array|Model $model): mixed
    {
        return $this->property ? ArrayHelper::getValue($model, $this->property) : null;
    }
}
