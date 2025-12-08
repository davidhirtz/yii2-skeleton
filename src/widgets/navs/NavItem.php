<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\navs;

use Closure;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Li;
use Hirtz\Skeleton\html\Span;
use Hirtz\Skeleton\html\traits\TagAttributesTrait;
use Hirtz\Skeleton\html\traits\TagContentTrait;
use Hirtz\Skeleton\html\traits\TagIconTrait;
use Hirtz\Skeleton\html\traits\TagLabelTrait;
use Hirtz\Skeleton\html\traits\TagUrlTrait;
use Hirtz\Skeleton\html\traits\TagVisibilityTrait;
use Hirtz\Skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\web\Controller;

class NavItem extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;
    use TagIconTrait;
    use TagLabelTrait;
    use TagUrlTrait;
    use TagVisibilityTrait;

    public array $linkAttributes = [];
    public array $labelAttributes = [];
    public array $badgeAttributes = ['class' => 'badge hidden md:block'];

    protected bool|null $active = null;
    protected int|string|null $badge = null;

    public function active(bool|callable $active): static
    {
        $this->active = $active instanceof Closure ? $active() : $active;
        return $this;
    }

    public function badge(int|string|null $badge): static
    {
        $this->badge = $badge ? (string)$badge : null;
        return $this;
    }

    public function badgeAttributes(array $attributes): static
    {
        $this->badgeAttributes = $attributes;
        return $this;
    }

    public function order(?int $order): static
    {
        $this->addStyle(['order' => $order]);
        return $this;
    }

    public function routes(array $routes): static
    {
        $request = Yii::$app->getRequest();

        if (Yii::$app->controller instanceof Controller) {
            foreach ($routes as $route => $params) {
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

        return $this;
    }

    protected function renderContent(): Stringable
    {
        return Li::make()
            ->attributes($this->attributes)
            ->addClass('nav-item')
            ->content($this->getContent());
    }

    public function getContent(): string|Stringable
    {
        if ($this->content) {
            return implode('', $this->content);
        }

        $link = A::make()
            ->attributes($this->linkAttributes)
            ->addClass('nav-link')
            ->href($this->url);

        if ($this->icon) {
            $link->addContent($this->icon
                ->attributes($this->iconAttributes));
        }

        if ($this->label) {
            $link->addContent(Span::make()
                ->text($this->label)
                ->attributes($this->labelAttributes));
        }

        if (null !== $this->badge) {
            $link->addContent(Span::make()
                ->text($this->badge)
                ->attributes($this->badgeAttributes));
        }

        $this->active ??= Yii::$app->getRequest()->url === ($link->attributes['href'] ?? null);

        if ($this->active) {
            $link->addClass('active');
        }

        return $link;
    }
}
