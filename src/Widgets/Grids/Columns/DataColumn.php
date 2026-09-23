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

/**
 * @template TModel of array|Model = Model
 * @extends Column<TModel>
 */
class DataColumn extends Column
{
    use FormatTrait;
    use PropertyTrait;

    /**
     * The closure a caller hands `value()` is typed against whichever model it re-binds the column to, which the
     * column itself has no way of holding — it applies it to the model the grid gives it.
     *
     * @var Closure(mixed, string|int=, int=):mixed|null
     */
    protected ?Closure $value = null;
    protected bool $enableSorting = true;

    /**
     * @var list<Closure>|null
     */
    private ?array $sortClosures = null;

    /**
     * @param array<string, mixed> $config
     */
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
        $this->sortClosures[] = $closure;
        return $this;
    }

    public function enableSorting(bool $enableSorting): static
    {
        $this->enableSorting = $enableSorting;
        return $this;
    }

    /**
     * @template TValueModel of array|Model
     * @param Closure(TValueModel, string|int=, int=):mixed|null $value
     * @return self<TValueModel>
     */
    public function value(?Closure $value): self
    {
        $this->value = $value;
        return $this;
    }

    #[Override]
    protected function getHeader(): string|Stringable
    {
        return $this->property !== null && $this->title !== false
            ? $this->evaluate($this->sortClosures, $this->getSort())
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

    /**
     * @param TModel $model
     */
    protected function getValue(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $value = $this->value instanceof Closure
            ? ($this->value)($model, $key, $index)
            : $this->getPropertyValue($model);

        return $this->formatValue($value);
    }

    /**
     * @param TModel $model
     */
    protected function getPropertyValue(array|Model $model): mixed
    {
        return $this->property ? ArrayHelper::getValue($model, $this->property) : null;
    }
}
