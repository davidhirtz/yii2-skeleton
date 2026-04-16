<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Html\Th;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Stringable;
use Yii;
use yii\base\Model;
use yii\helpers\Html;

class Column
{
    use ContainerConfigurationTrait;
    use GridTrait;
    use VisibilityTrait;
    use TitleTrait;

    protected ?array $headerAttributes = null;

    protected ?Closure $content = null;
    protected array|null|Closure $contentAttributes = null;
    protected string $emptyCell = '&nbsp;';

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
    }

    public function content(Closure|null $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function contentAttributes(array|Closure|null $attributes): static
    {
        $this->contentAttributes = $attributes;
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
        Html::addCssClass($this->headerAttributes, "hidden md:table-cell");
        Html::addCssClass($this->contentAttributes, "hidden md:table-cell");

        return $this;
    }

    public function hiddenForMediumDevices(): static
    {
        Html::addCssClass($this->headerAttributes, "hidden lg:table-cell");
        Html::addCssClass($this->contentAttributes, "hidden lg:table-cell");

        return $this;
    }

    public function renderHeader(): Th
    {
        return Th::make()
            ->attributes($this->headerAttributes ?? [])
            ->content($this->getHeaderContent());
    }

    protected function getHeaderContent(): string|Stringable
    {
        return $this->title ?: $this->emptyCell;
    }

    public function renderBody(array|Model $model, string|int $key, int $index): Td
    {
        $attributes = $this->contentAttributes instanceof Closure
            ? ($this->contentAttributes)($model, $key, $index, $this)
            : $this->contentAttributes;

        $attributes ??= [];
        $content = $this->getBodyContent($model, $key, $index);

        if ($content instanceof Td) {
            return $content->addAttributes($attributes);
        }

        return Td::make()
            ->attributes($attributes)
            ->content($content);
    }

    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = $this->content;

        if ($content instanceof Closure) {
            $content = ($this->content)($model, $key, $index, $this);

            if (is_array($content)) {
                $content = implode('', $content);
            }
        }

        return $content ?? $this->emptyCell;
    }
}
