<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Override;
use Stringable;
use yii\base\Model;

/**
 * @template TModel of array|Model
 * @extends DataColumn<TModel>
 */
class LinkColumn extends DataColumn
{
    protected array $linkAttributes = [];
    private ?array $linkCallbacks = null;

    /**
     * @var Closure(TModel, string|int=, int=):(array|string|null|false)|null
     */
    protected ?Closure $url = null;

    public function __construct(array $config = [])
    {
        $this->content ??= $this->getLink(...);
        parent::__construct($config);
    }

    public function blank(): static
    {
        $this->target('_blank');
        return $this;
    }

    /**
     * @param Closure(A|Div):(string|Stringable) $closure
     */
    public function link(Closure $closure): static
    {
        $this->linkCallbacks[] = $closure;
        return $this;
    }

    public function target(?string $target): static
    {
        return $this->link(fn (A|Div $tag) => $tag instanceof A ? $tag->target($target) : $tag);
    }

    /**
     * @template TUrlModel of array|Model
     * @param Closure(TUrlModel, string|int=, int=):(array|string|null|false)|null $url
     * @return self<TUrlModel>
     */
    public function url(?Closure $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Keeps the {@see LinkColumn} type (and its {@see self::url()} method) when the model type is
     * re-bound through {@see DataColumn::value()}.
     *
     * @template TValueModel of array|Model
     * @param Closure(TValueModel, string|int=, int=):mixed|null $value
     * @return self<TValueModel>
     */
    #[Override]
    public function value(?Closure $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param TModel $model
     */
    protected function getLink(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = $this->getValue($model, $key, $index);

        if ($content === '') {
            return $content;
        }

        $href = $this->url ? ($this->url)($model, $key, $index) : null;

        if ($href) {
            return $this->evaluate($this->linkCallbacks, A::make()
                ->attributes($this->linkAttributes)
                ->content($content)
                ->href($href));
        }

        return $this->linkCallbacks || $this->linkAttributes
            ? $this->evaluate($this->linkCallbacks, Div::make()
                ->attributes($this->linkAttributes)
                ->content($content))
            : $content;
    }
}
