<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Div;
use Override;
use Stringable;
use yii\base\Model;

class LinkColumn extends DataColumn
{
    protected array $linkAttributes = [];
    protected ?Closure $href = null;

    public function href(Closure $href): static
    {
        $this->href = $href;
        return $this;
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = parent::getBodyContent($model, $key, $index);
        $content = parent::getBodyContent($model, $key, $index);

        if (!$content) {
            return $content;
        }

        $href = $this->href ? call_user_func($this->href, $model, $key, $index, $this) : null;

        if ($href) {
            return A::make()
                ->attributes($this->linkAttributes)
                ->html($content)
                ->href($href);
        }

        return $this->linkAttributes
            ? Div::make()
                ->attributes($this->linkAttributes)
                ->html($content)
            : $content;
    }
}
