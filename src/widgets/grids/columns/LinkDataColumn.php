<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use yii\base\Model;
use yii\i18n\Formatter;

class LinkDataColumn extends DataColumn
{
    public function __construct(
        GridView $grid,
        Formatter $formatter,
        ?string $attribute = null,
        ?string $label = null,
        protected array|string|null|Closure $route = null,
        protected array $wrapperAttributes = [],
        Closure|string|null $value = null,
        string $format = 'text',
        Closure|string|null $content = null,
        array|Closure $contentAttributes = [],
        ?string $header = null,
        array $headerAttributes = [],
        array $sortLinkAttributes = [],
        bool $visible = true,
        bool $enableSorting = true,
        bool $encodeLabel = true,
        string $emptyCell = '&nbsp;'
    ) {
        parent::__construct(
            $grid,
            $formatter,
            $attribute,
            $label,
            $value,
            $format,
            $content,
            $contentAttributes,
            $header,
            $headerAttributes,
            $sortLinkAttributes,
            $visible,
            $enableSorting,
            $encodeLabel,
            $emptyCell,
        );
    }

    #[\Override]
    protected function renderDataCellContent(Model $model, string|int $key, int $index): string|Stringable
    {
        $route = is_callable($this->route) ? call_user_func($this->route, $model) : $this->route;
        $content = parent::renderDataCellContent($model, $key, $index);

        if (!$content || $content === $this->grid->emptyCell) {
            return $content;
        }

        if ($route) {
            return Html::a($content, $route, $this->wrapperAttributes);
        }

        return $this->wrapperAttributes ? Html::tag('div', $content, $this->wrapperAttributes) : $content;
    }
}
