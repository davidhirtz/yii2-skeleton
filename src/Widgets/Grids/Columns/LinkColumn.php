<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Base\Traits\EvaluateClosureTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Override;
use Stringable;
use yii\base\Model;

class LinkColumn extends PropertyColumn
{
    use EvaluateClosureTrait;

    protected ?Closure $url = null;

    protected ?string $target = null;

    /**
     * @var Closure(A|Div):(string|Stringable)[]|null
     */
    private ?array $linkClosures = null;

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
        $this->target = $target;
        return $this;
    }

    public function url(Closure $url): static
    {
        $this->url = $url;
        return $this;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = parent::getBodyContent($model, $key, $index);

        if (!$content) {
            return $content;
        }

        $href = $this->url ? ($this->url)($model, $key, $index, $this) : null;

        if ($href) {
            return $this->evaluate($this->linkClosures, A::make()
                ->content($content)
                ->href($href)
                ->target($this->target));
        }

        return $this->linkClosures
            ? $this->evaluate($this->linkClosures, Div::make()->content($content))
            : $content;
    }
}
