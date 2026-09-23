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
 * @template TModel of array|Model = Model
 * @extends DataColumn<TModel>
 */
class LinkColumn extends DataColumn
{
    /**
     * @var array<string, mixed>
     */
    protected array $linkAttributes = [];
    /**
     * @var list<Closure>|null
     */
    private ?array $linkClosures = null;

    /**
     * The closure a caller hands `url()` is typed against whichever model it re-binds the column to, which the
     * column itself has no way of holding — it applies it to the model the grid gives it.
     *
     * @var Closure(mixed, string|int=, int=):(array<int|string, mixed>|string|null|false)|null
     */
    protected ?Closure $url = null;

    /**
     * @param array<string, mixed> $config
     */
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
        $this->linkClosures[] = $closure;
        return $this;
    }

    public function target(?string $target): static
    {
        return $this->link(fn (A|Div $tag) => $tag instanceof A ? $tag->target($target) : $tag);
    }

    /**
     * @template TUrlModel of array|Model = TModel
     * @param Closure(TUrlModel, string|int=, int=):(array<int|string, mixed>|string|null|false)|null $url
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
            return $this->evaluate($this->linkClosures, A::make()
                ->attributes($this->linkAttributes)
                ->content($content)
                ->href($href));
        }

        return $this->linkClosures || $this->linkAttributes
            ? $this->evaluate($this->linkClosures, Div::make()
                ->attributes($this->linkAttributes)
                ->content($content))
            : $content;
    }
}
