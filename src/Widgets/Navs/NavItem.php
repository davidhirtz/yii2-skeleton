<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\web\Controller;

class NavItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use IconTrait;
    use UrlTrait;
    use VisibilityTrait;

    protected ?bool $active = null;
    protected ?Span $badge = null;
    protected ?Span $label = null;
    protected ?Closure $link = null;
    protected array $routes = [];

    public function active(bool|callable|null $active): static
    {
        $this->active = $active instanceof Closure ? $active() : $active;
        return $this;
    }

    /**
     * @param Closure(Span):(int|string|null)|int|string|null $badge
     * @return $this
     */
    public function badge(Closure|int|string|null $badge): static
    {
        $this->badge ??= $badge ? Span::make()->class('badge hidden md:block') : null;

        if ($this->badge) {
            $this->badge = is_callable($badge)
                ? $badge($this->badge)
                : $this->badge->text(is_int($badge) ? Yii::$app->getFormatter()->asInteger($badge) : $badge);
        }

        return $this;
    }

    /**
     * @param Closure(Span):(int|string|null)|string|null $label
     * @return $this
     */
    public function label(Closure|string|null $label): static
    {
        $this->label ??= $label ? Span::make() : null;

        if ($this->label) {
            $this->label = is_callable($label) ? $label($this->label) : $this->label->text($label);
        }

        return $this;
    }

    /**
     * @param Closure(A):(string|Stringable)|null $link
     * @return $this
     */
    public function link(?Closure $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function order(?int $order): static
    {
        return $order !== null ? $this->addStyle(['order' => $order]) : $this->removeStyle(['order']);
    }

    public function routes(array $routes): static
    {
        $this->routes = [...$this->routes, ...$routes];
        return $this;
    }

    protected function configure(): void
    {
        $this->parseRoutes();
        parent::configure();
    }

    protected function parseRoutes(): void
    {
        if (!(Yii::$app->controller instanceof Controller) || $this->active) {
            return;
        }

        $request = Yii::$app->getRequest();

        foreach ($this->routes as $route => $params) {
            if (is_int($route)) {
                $route = is_array($params) ? array_shift($params) : $params;
            }

            $shouldSkip = ('!' === $route[0]);

            if ($shouldSkip) {
                $route = substr((string)$route, 1);
            }

            if (preg_match("~$route~", Yii::$app->controller->route)) {
                if (is_array($params)) {
                    foreach ($params as $key => $value) {
                        $isMatching = is_int($key)
                            ? in_array($value, array_keys($request->get()), true)
                            : $request->get($key) === $value;

                        if ($isMatching) {
                            $this->active = true;
                            break 2;
                        }
                    }

                    $this->active = false;
                    break;
                }

                $this->active = !$shouldSkip;
                break;
            }
        }
    }

    #[Override]
    protected function renderContent(): Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass('nav-item')
            ->content($this->renderLink());
    }

    protected function renderLink(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->class('nav-link')
            ->content($this->icon, $this->label, $this->badge)
            ->href($this->url);

        $this->active ??= Yii::$app->getRequest()->getUrl() === ($link->attributes['href'] ?? null);

        if ($this->active) {
            $link->addClass('active');
        }

        return $this->link ? ($this->link)($link) : $link;
    }
}
