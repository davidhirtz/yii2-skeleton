<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Closure;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Base\Traits\EvaluateClosureTrait;
use Hirtz\Skeleton\Html\Base\Tag;
use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Html\Th;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Stringable;
use Yii;
use yii\base\Model;

/**
 * @template TModel of array|Model
 */
class Column
{
    use ContainerConfigurationTrait;
    use EvaluateClosureTrait;
    use GridTrait;
    use TitleTrait;
    use VisibilityTrait;

    protected array $bodyAttributes = [];
    protected array $headerAttributes = [];

    protected Closure $content;
    protected string $emptyCell = '';

    private ?array $bodyCallbacks = null;
    private ?array $headerCallbacks = null;

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
    }

    /**
     * @param Closure(Td):Td $closure
     * @return $this
     */
    public function body(Closure $closure): static
    {
        $this->bodyCallbacks[] = $closure;
        return $this;
    }

    public function content(Closure|null $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param Closure(Th):Th $closure
     * @return $this
     */
    public function header(Closure $closure): static
    {
        $this->headerCallbacks[] = $closure;
        return $this;
    }

    public function centered(): static
    {
        $callback = fn (Tag $tag) => $tag->addClass('text-center');
        return $this->body($callback)->header($callback);
    }

    public function nowrap(): static
    {
        return $this->body(fn (Td $tag) => $tag->addClass('text-nowrap'));
    }

    public function hiddenForSmallDevices(): static
    {
        $closure = fn (Tag $tag) => $tag->addClass('hidden md:table-cell');
        return $this->body($closure)->header($closure);
    }

    public function hiddenForMediumDevices(): static
    {
        $closure = fn (Tag $tag) => $tag->addClass('hidden lg:table-cell');
        return $this->body($closure)->header($closure);
    }

    public function width(int $width): static
    {
        return $this->header(fn (Th $tag) => $tag->attribute('width', $width));
    }

    /**
     * @param TModel $model
     */
    public function renderBody(array|Model $model, string|int $key, int $index): Td
    {
        $body = $this->getBody($model, $key, $index);

        if (!$body instanceof Td) {
            $body = Td::make()
                ->attributes($this->bodyAttributes)
                ->content($body);
        }

        return $this->evaluate($this->bodyCallbacks, $body);
    }

    /**
     * @param TModel $model
     */
    protected function getBody(array|Model $model, string|int $key, int $index): string|Stringable
    {
        $content = ($this->content)($model, $key, $index, $this);
        $content = is_array($content) ? implode('', $content) : (string)$content;

        return $content === '' ? $this->emptyCell : $content;
    }

    public function renderHeader(): Th
    {
        $header = Th::make()
            ->attributes($this->headerAttributes)
            ->content($this->getHeader());

        return $this->evaluate($this->headerCallbacks, $header);
    }

    protected function getHeader(): string|Stringable
    {
        return $this->title ?: $this->emptyCell;
    }
}
