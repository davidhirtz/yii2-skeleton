<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Closure;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Buttons\Badge;
use Hirtz\Skeleton\Widgets\Navs\Traits\NavItemTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
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
    use LabelTrait;
    use NavItemTrait;
    use UrlTrait;
    use VisibilityTrait;

    protected ?bool $active = null;
    protected Closure|string|int|null $badge = null;
    protected ?Closure $link = null;
    protected array $routes = [];
    protected ?int $order = null;

    /**
     * @param bool|Closure():(bool|null)|null $active
     * @return $this
     */
    public function active(bool|Closure|null $active): static
    {
        $this->active = $active instanceof Closure ? $active() : $active;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * @param Closure(Badge):(?Badge)|int|string|null $badge
     * @return $this
     */
    public function badge(Closure|int|string|null $badge): static
    {
        $this->badge = $badge;
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
        $this->order = $order;
        return $this;
    }

    public function routes(array $routes): static
    {
        $this->routes = [...$this->routes, ...$routes];
        return $this;
    }


    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->isVisible()) {
            return '';
        }

        $items = $this->renderItems();

        if ($this->order !== null) {
            $this->addStyle(['order' => $this->order]);
        }

        return Li::make()
            ->attributes($this->attributes)
            ->addClass('nav-item')
            ->content($this->getContent(), $items);
    }

    protected function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->class('nav-link')
            ->href($this->url);

        $link->addContent($this->getIcon()?->addClass('nav-link-icon'));

        if ($this->label) {
            $link->addContent(Span::make()->text($this->label));
        }

        if ($this->badge) {
            $badge = Badge::make()->addClass('aside-badge');
            $badge = $this->badge instanceof Closure ? ($this->badge)($badge) : $badge->value($this->badge);

            $link->addContent($badge);
        }

        $this->active ??= $this->hasActiveSubnavItem() ?: $this->hasActiveRoute();
        $this->active ??= Yii::$app->getRequest()->getUrl() === ($link->attributes['href'] ?? null);

        if ($this->active) {
            $link->addClass('active');
        }

        return $this->link ? ($this->link)($link) : $link;
    }

    protected function renderItems(): ?string
    {
        return $this->items
            ? Nav::make()
                ->class('subnav')
                ->items($this->items)
                ->render()
            : null;
    }

    protected function hasActiveSubnavItem(): bool
    {
        foreach ($this->items as $item) {
            if ($item->isActive()) {
                return true;
            }
        }

        return false;
    }

    protected function hasActiveRoute(): ?bool
    {
        if (!(Yii::$app->controller instanceof Controller)) {
            return null;
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
                            return true;
                        }
                    }

                    return false;
                }

                return !$shouldSkip;
            }
        }

        return null;
    }
}
