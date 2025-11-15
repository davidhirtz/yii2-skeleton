<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\Td;
use davidhirtz\yii2\skeleton\html\Th;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\GridTrait;
use Stringable;
use Yii;
use yii\base\Model;
use yii\helpers\Html;

class Column
{
    use ContainerConfigurationTrait;
    use GridTrait;
    use TagVisibilityTrait;

    protected string|false|null $header = null;

    public string|Stringable|Closure|null $content = null;
    public array|null|Closure $contentAttributes = null;
    public ?array $headerAttributes = null;
    public string $emptyCell = '&nbsp;';

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

    public function header(string|false|null $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function headerAttributes(array|null $attributes): static
    {
        $this->headerAttributes = $attributes;
        return $this;
    }

    public function centered(): static
    {
        Html::addCssClass($this->headerAttributes, 'text-center');
        Html::addCssClass($this->contentAttributes, 'text-center');

        return $this;
    }

    public function nowrap(): static
    {
        Html::addCssClass($this->contentAttributes, 'text-nowrap');
        return $this;
    }

    public function hiddenForSmallDevices(): static
    {
        Html::addCssClass($this->headerAttributes, "d-none d-md-table-cell");
        Html::addCssClass($this->contentAttributes, "d-none d-md-table-cell");

        return $this;
    }

    public function hiddenForMediumDevices(): static
    {
        Html::addCssClass($this->headerAttributes, "d-none d-lg-table-cell");
        Html::addCssClass($this->contentAttributes, "d-none d-lg-table-cell");

        return $this;
    }

    public function renderHeader(): Th
    {
        return Th::make()
            ->content($this->getHeaderContent())
            ->attributes($this->headerAttributes ?? []);
    }

    protected function getHeaderContent(): string|Stringable
    {
        return $this->header ?: $this->emptyCell;
    }

    public function renderBody(array|Model $model, string|int $key, int $index): Td
    {
        $attributes = $this->contentAttributes instanceof Closure
            ? call_user_func($this->contentAttributes, $model, $key, $index, $this)
            : $this->contentAttributes;

        return Td::make()
            ->content($this->getBodyContent($model, $key, $index))
            ->attributes($attributes ?? []);
    }

    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content instanceof Closure) {
            $content = call_user_func($this->content, $model, $key, $index, $this);
            return is_array($content) ? implode('', $content) : $content;
        }

        return $this->content ?? $this->emptyCell;
    }
}
