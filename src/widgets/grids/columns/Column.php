<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\html\Td;
use davidhirtz\yii2\skeleton\html\Th;
use davidhirtz\yii2\skeleton\widgets\grids\columns\interfaces\ColumnInterface;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Stringable;
use yii\base\Model;

class Column implements ColumnInterface
{
    protected GridView $grid;
    protected string|null|Closure $content = null;
    protected array|null|Closure $contentAttributes = null;
    protected ?string $header = null;
    protected ?array $headerAttributes = null;
    protected string $emptyCell = '&nbsp;';

    public function grid(GridView $grid): static
    {
        $this->grid = $grid;
        return $this;
    }

    public function content(string|Closure|null $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function contentAttributes(array|Closure|null $attributes): static
    {
        $this->contentAttributes = $attributes;
        return $this;
    }

    public function header(string|null $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function headerAttributes(array|null $attributes): static
    {
        $this->headerAttributes = $attributes;
        return $this;
    }

    public function renderHeader(): Th
    {
        return Th::make()
            ->html($this->renderHeaderCellContent())
            ->attributes($this->headerAttributes ?? []);
    }

    protected function renderHeaderCellContent(): string|Stringable
    {
        return $this->header ?? $this->emptyCell;
    }

    public function renderBody(Model $model, string|int $key, int $index): Td
    {
        $attributes = $this->contentAttributes instanceof Closure
            ? call_user_func($this->contentAttributes, $model, $key, $index, $this)
            : $this->contentAttributes;

        return Td::make()
            ->html($this->renderDataCellContent($model, $key, $index))
            ->attributes($attributes ?? []);
    }

    protected function renderDataCellContent(Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content instanceof Closure) {
            return call_user_func($this->content, $model, $key, $index, $this);
        }

        return $this->content ?? $this->emptyCell;
    }
}
