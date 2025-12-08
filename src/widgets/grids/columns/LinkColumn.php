<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\grids\columns;

use Closure;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Div;
use Override;
use Stringable;
use yii\base\Model;

class LinkColumn extends DataColumn
{
    protected array $linkAttributes = [];
    protected ?Closure $url = null;

    public function linkAttributes(array $attributes): static
    {
        $this->linkAttributes = $attributes;
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

        $href = $this->url ? call_user_func($this->url, $model, $key, $index, $this) : null;

        if ($href) {
            return A::make()
                ->attributes($this->linkAttributes)
                ->content($content)
                ->href($href);
        }

        return $this->linkAttributes
            ? Div::make()
                ->attributes($this->linkAttributes)
                ->content($content)
            : $content;
    }
}
