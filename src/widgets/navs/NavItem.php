<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs;

use Closure;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\html\Span;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\html\traits\TagUrlTrait;
use davidhirtz\yii2\skeleton\html\traits\TagVisibilityTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\web\Controller;

class NavItem extends Widget
{
    use TagAttributesTrait;
    use TagLabelTrait;
    use TagUrlTrait;
    use TagVisibilityTrait;

    public array $linkAttributes = [];
    public array $iconAttributes = ['class' => 'fa-fw'];
    public array $labelAttributes = [];
    public array $badgeAttributes = ['class' => 'badge d-none d-lg-block'];

    protected bool|null $active = null;
    protected array $content = [];
    protected int|string|null $badge = null;
    protected string|null $icon = null;

    public function active(bool|callable $active): static
    {
        $this->active = $active instanceof Closure ? $active() : $active;
        return $this;
    }

    public function content(string|Stringable|null ...$content): static
    {
        $this->content = array_values(array_filter($content));
        return $this;
    }

    public function badge(int|string|null $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    public function icon(string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function order(int $order): static
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

                $shouldSkip = ($route[0] === '!');

                if ($shouldSkip) {
                    $route = substr((string)$route, 1);
                }

                if (preg_match("~$route~", Yii::$app->controller->route)) {
                    if (is_array($params)) {
                        foreach ($params as $key => $value) {
                            if ((is_int($key) && !in_array($value, array_keys($request->get()), true))
                                || (is_string($key) && $request->get($key) !== $value)) {
                                continue 2;
                            }
                        }
                    }

                    if (!$shouldSkip) {
                        $this->active = true;
                    }

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
            $link->addContent(Icon::make()
                ->name($this->icon)
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
