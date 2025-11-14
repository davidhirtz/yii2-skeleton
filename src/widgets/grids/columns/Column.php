<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\Td;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Stringable;
use yii\base\Model;

class Column
{
    public function __construct(
        protected readonly GridView $grid,
        protected string|null|Closure $content = null,
        protected array|Closure $contentAttributes = [],
        protected ?string $header = null,
        protected array $headerAttributes = [],
        protected bool $visible = true,
        protected string $emptyCell = '&nbsp;',
    ) {
    }

    public function renderHeaderCell(): Td
    {
        return Td::make()
            ->html($this->renderHeaderCellContent())
            ->attributes($this->headerAttributes);
    }

    protected function renderHeaderCellContent(): string|Stringable
    {
        return $this->header ?? $this->emptyCell;
    }

    public function renderDataCell(Model $model, string|int $key, int $index): Td
    {
        $attributes = $this->contentAttributes instanceof Closure
            ? call_user_func($this->contentAttributes, $model, $key, $index, $this)
            : $this->contentAttributes;

        return Td::make()
            ->html($this->renderDataCellContent($model, $key, $index))
            ->attributes($attributes);
    }

    protected function renderDataCellContent(Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content instanceof Closure) {
            return call_user_func($this->content, $model, $key, $index, $this);
        }

        return $this->content ?? $this->emptyCell;
    }
}
