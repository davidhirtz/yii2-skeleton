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
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\LinkTrait;
use Hirtz\Skeleton\Widgets\Traits\OrderTrait;
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

    /** @use ItemTrait<NavItem> */
    use ItemTrait;

    use IconTrait;
    use LabelTrait;
    use LinkTrait;
    use OrderTrait;
    use UrlTrait;
    use VisibilityTrait;

    protected ?bool $active = null;
    protected Closure|string|int|null $badge = null;
    protected array $routes = [];

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

    public function routes(array $routes): static
    {
        $this->routes = [...$this->routes, ...$routes];
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $items = $this->getItems();

        if (!$this->isVisible()) {
            return '';
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

        $this->active ??= $this->hasActiveRoute() ?? Yii::$app->getRequest()->getUrl() === $link->attributes['href'];

        if ($this->active) {
            $link->addClass('active');
        }

        return $this->getLink($link);
    }

    protected function hasActiveRoute(): ?bool
    {
        if (!(Yii::$app->controller instanceof Controller)) {
            return false;
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

    protected function getItems(): ?string
    {
        if (!$this->items) {
            return null;
        }

        $items = Nav::make()
            ->class('subnav')
            ->items($this->items)
            ->render();

        foreach ($this->items as $item) {
            if ($item->isActive()) {
                $this->active = true;
            }

            $this->roles($item->getRoles());
        }

        return $items;
    }
}
