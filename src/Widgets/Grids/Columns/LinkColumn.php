<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Stringable;
use yii\base\Model;

class LinkColumn extends PropertyColumn
{
    protected array $linkAttributes = [];
    protected ?Closure $url = null;

    private ?array $linkCallbacks = null;

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

    public function url(Closure $url): static
    {
        $this->url = $url;
        return $this;
    }

    protected function getLink(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = $this->getValue($model, $key, $index);

        if ($content === '') {
            return $content;
        }

        $href = $this->url ? ($this->url)($model, $key, $index, $this) : null;

        if ($href) {
            return $this->evaluate($this->linkCallbacks, A::make()
                ->attributes($this->linkAttributes)
                ->text($content)
                ->href($href));
        }

        return $this->linkCallbacks
            ? $this->evaluate($this->linkCallbacks, Div::make()
                ->attributes($this->linkAttributes)
                ->text($content))
            : $content;
    }
}
